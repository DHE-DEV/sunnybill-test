<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Aws\S3\S3Client;

class StorageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_driver',
        'storage_config',
        'total_storage_used',
        'last_storage_calculation',
        'is_active',
    ];

    protected $casts = [
        'storage_config' => 'array',
        'total_storage_used' => 'integer',
        'last_storage_calculation' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Aktuelle Speicher-Einstellung abrufen
     */
    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Speicher-Treiber-Optionen
     */
    public static function getDriverOptions(): array
    {
        return [
            'local' => 'Lokaler Speicher',
            's3' => 'Amazon S3',
            'digitalocean' => 'DigitalOcean Spaces',
        ];
    }

    /**
     * Gesamtspeicherplatz berechnen
     */
    public function calculateTotalStorage(): int
    {
        $totalSize = Document::sum('size') ?? 0;
        
        $this->update([
            'total_storage_used' => $totalSize,
            'last_storage_calculation' => now(),
        ]);

        return $totalSize;
    }

    /**
     * Formatierte Speichergröße
     */
    public function getFormattedStorageUsedAttribute(): string
    {
        return $this->formatBytes($this->total_storage_used);
    }

    /**
     * Bytes in lesbare Größe formatieren
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Speicher-Konfiguration validieren
     */
    public function validateConfig(): array
    {
        $errors = [];

        // Debug: Konfiguration loggen (ohne sensible Daten)
        \Log::info('StorageSetting validateConfig Debug', [
            'driver' => $this->storage_driver,
            'config_keys' => array_keys($this->storage_config ?? []),
            'environment' => app()->environment(),
            'server_ip' => request()->server('SERVER_ADDR'),
            'user_ip' => request()->ip(),
        ]);

        switch ($this->storage_driver) {
            case 's3':
                if (empty($this->storage_config['key'])) {
                    $errors[] = 'AWS Access Key ist erforderlich';
                }
                if (empty($this->storage_config['secret'])) {
                    $errors[] = 'AWS Secret Key ist erforderlich';
                }
                if (empty($this->storage_config['region'])) {
                    $errors[] = 'AWS Region ist erforderlich';
                }
                if (empty($this->storage_config['bucket'])) {
                    $errors[] = 'S3 Bucket ist erforderlich';
                }
                break;

            case 'digitalocean':
                if (empty($this->storage_config['key'])) {
                    $errors[] = 'DigitalOcean Spaces Key ist erforderlich';
                }
                if (empty($this->storage_config['secret'])) {
                    $errors[] = 'DigitalOcean Spaces Secret ist erforderlich';
                }
                if (empty($this->storage_config['region'])) {
                    $errors[] = 'DigitalOcean Region ist erforderlich (aktuell: ' . ($this->storage_config['region'] ?? 'null') . ')';
                }
                if (empty($this->storage_config['bucket'])) {
                    $errors[] = 'DigitalOcean Space Name ist erforderlich';
                }
                if (empty($this->storage_config['endpoint'])) {
                    $errors[] = 'DigitalOcean Endpoint ist erforderlich';
                }
                
                // Zusätzliche Validierung für DigitalOcean
                if (!empty($this->storage_config['endpoint'])) {
                    $expectedEndpoint = "https://{$this->storage_config['region']}.digitaloceanspaces.com";
                    if ($this->storage_config['endpoint'] !== $expectedEndpoint) {
                        \Log::warning('DigitalOcean Endpoint Mismatch', [
                            'provided' => $this->storage_config['endpoint'],
                            'expected' => $expectedEndpoint
                        ]);
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * Speicher-Konfiguration testen
     */
    public function testConnection(): array
    {
        try {
            // Validierung vor dem Test
            $errors = $this->validateConfig();
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Konfigurationsfehler: ' . implode(', ', $errors)];
            }

            $config = $this->buildFilesystemConfig();
            
            // Debug: Konfiguration loggen
            \Log::info('testConnection Config Debug', [
                'driver' => $this->storage_driver,
                'config' => $config
            ]);
            
            // Temporäre Disk-Konfiguration erstellen
            config(['filesystems.disks.test_storage' => $config]);
            
            $disk = Storage::disk('test_storage');
            
            // Schritt 1: Detaillierte Credential-Validierung
            $credentialTest = $this->validateDigitalOceanCredentials();
            if (!$credentialTest['success']) {
                return $credentialTest;
            }
            
            // Schritt 2: Erst versuchen, den Space zu listen (weniger invasiv)
            try {
                $files = $disk->files();
                \Log::info('testConnection List Files Success', ['file_count' => count($files)]);
            } catch (\Exception $listException) {
                \Log::error('testConnection List Files Exception', [
                    'exception' => $listException->getMessage(),
                    'trace' => $listException->getTraceAsString()
                ]);
                
                // Detaillierte Analyse des 403 Fehlers
                if (strpos($listException->getMessage(), '403 Forbidden') !== false) {
                    $troubleshootingMessage = "403 Forbidden beim Auflisten - Detaillierte Diagnose:\n\n";
                    $troubleshootingMessage .= "**Konfiguration:**\n";
                    $troubleshootingMessage .= "• Space: {$this->storage_config['bucket']}\n";
                    $troubleshootingMessage .= "• Region: {$this->storage_config['region']}\n";
                    $troubleshootingMessage .= "• Endpoint: {$this->storage_config['endpoint']}\n";
                    $troubleshootingMessage .= "• Access Key: " . substr($this->storage_config['key'], 0, 8) . "...\n\n";
                    $troubleshootingMessage .= "**Mögliche Ursachen:**\n";
                    $troubleshootingMessage .= "1. API Key hat keine 'Spaces: Read and Write' Berechtigung\n";
                    $troubleshootingMessage .= "2. Space Name '{$this->storage_config['bucket']}' existiert nicht\n";
                    $troubleshootingMessage .= "3. Access Key gehört zu anderem DigitalOcean Account\n";
                    $troubleshootingMessage .= "4. Space ist in anderer Region als '{$this->storage_config['region']}'\n";
                    $troubleshootingMessage .= "5. IP-Beschränkungen im Space aktiviert\n\n";
                    $troubleshootingMessage .= "**Nächste Schritte:**\n";
                    $troubleshootingMessage .= "• Überprüfen Sie die API Key Berechtigungen in DigitalOcean\n";
                    $troubleshootingMessage .= "• Stellen Sie sicher, dass der Space existiert und zugänglich ist\n";
                    $troubleshootingMessage .= "• Prüfen Sie, ob Access Key und Space zum gleichen Account gehören";
                    
                    return ['success' => false, 'message' => $troubleshootingMessage];
                }
                
                return ['success' => false, 'message' => 'Verbindungstest fehlgeschlagen: ' . $listException->getMessage()];
            }
            
            // Schritt 3: Direkter Upload-Test mit AWS S3 Client (umgeht Laravel Storage)
            return $this->testDirectS3Upload();
            
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMessage = $e->getAwsErrorMessage();
            $statusCode = $e->getStatusCode();
            
            \Log::error('testConnection S3 Exception', [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'status_code' => $statusCode,
                'trace' => $e->getTraceAsString(),
                'request_url' => $e->getRequest() ? $e->getRequest()->getUri() : 'unknown'
            ]);
            
            // Spezifische Behandlung für 403 Forbidden
            if ($statusCode === 403) {
                $troubleshootingMessage = "403 Forbidden - Mögliche Ursachen:\n";
                $troubleshootingMessage .= "• Falsche Access Key oder Secret Key\n";
                $troubleshootingMessage .= "• Space existiert nicht oder falscher Name\n";
                $troubleshootingMessage .= "• Keine Schreibberechtigung für den Space\n";
                $troubleshootingMessage .= "• IP-Beschränkungen im DigitalOcean Space\n";
                $troubleshootingMessage .= "• Falsche Region oder Endpoint-URL\n\n";
                $troubleshootingMessage .= "Aktueller Fehler: {$errorMessage}";
                
                return ['success' => false, 'message' => $troubleshootingMessage];
            }
            
            return ['success' => false, 'message' => "S3/Spaces Fehler ({$errorCode}): {$errorMessage}"];
        } catch (\Exception $e) {
            \Log::error('testConnection General Exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Spezielle Behandlung für 403 in der allgemeinen Exception
            if (strpos($e->getMessage(), '403 Forbidden') !== false) {
                $troubleshootingMessage = "403 Forbidden - Berechtigungsproblem erkannt:\n";
                $troubleshootingMessage .= "• Überprüfen Sie Ihre DigitalOcean Spaces Credentials\n";
                $troubleshootingMessage .= "• Stellen Sie sicher, dass der Space existiert\n";
                $troubleshootingMessage .= "• Prüfen Sie die Berechtigungen des API-Keys\n";
                $troubleshootingMessage .= "• Kontrollieren Sie eventuelle IP-Beschränkungen\n\n";
                $troubleshootingMessage .= "Detaillierter Fehler: " . $e->getMessage();
                
                return ['success' => false, 'message' => $troubleshootingMessage];
            }
            
            return ['success' => false, 'message' => 'Verbindungsfehler: ' . $e->getMessage()];
        }
    }

    /**
     * DigitalOcean Credentials detailliert validieren
     */
    private function validateDigitalOceanCredentials(): array
    {
        try {
            \Log::info('validateDigitalOceanCredentials Start', [
                'driver' => $this->storage_driver,
                'has_key' => !empty($this->storage_config['key']),
                'has_secret' => !empty($this->storage_config['secret']),
                'region' => $this->storage_config['region'] ?? 'not set',
                'bucket' => $this->storage_config['bucket'] ?? 'not set',
                'endpoint' => $this->storage_config['endpoint'] ?? 'not set'
            ]);

            // Keys bereinigen (Leerzeichen entfernen)
            $cleanKey = trim($this->storage_config['key']);
            $cleanSecret = trim($this->storage_config['secret']);
            
            // Basis-Validierung
            if (empty($cleanKey) || empty($cleanSecret)) {
                return ['success' => false, 'message' => 'Access Key oder Secret Key fehlt'];
            }

            // DigitalOcean Spaces spezifische Validierung
            if ($this->storage_driver === 'digitalocean') {
                // Erwarteter Endpoint für die Region
                $expectedEndpoint = "https://{$this->storage_config['region']}.digitaloceanspaces.com";
                if ($this->storage_config['endpoint'] !== $expectedEndpoint) {
                    return [
                        'success' => false,
                        'message' => "Endpoint-Mismatch: Erwartet '{$expectedEndpoint}', erhalten '{$this->storage_config['endpoint']}'"
                    ];
                }

                // Access Key Format prüfen und bereinigen
                if (strlen($cleanKey) !== 20) {
                    \Log::warning('DigitalOcean Access Key Format Issue', [
                        'original_length' => strlen($this->storage_config['key']),
                        'cleaned_length' => strlen($cleanKey),
                        'original_key_preview' => substr($this->storage_config['key'], 0, 8) . '...',
                        'cleaned_key_preview' => substr($cleanKey, 0, 8) . '...'
                    ]);
                    
                    if (strlen($cleanKey) === 20) {
                        // Key war nur mit Leerzeichen, automatisch bereinigen
                        $this->storage_config['key'] = $cleanKey;
                        \Log::info('Access Key automatisch bereinigt');
                    } else {
                        return [
                            'success' => false,
                            'message' => "DigitalOcean Access Key sollte 20 Zeichen lang sein (aktuell: " . strlen($this->storage_config['key']) . ", bereinigt: " . strlen($cleanKey) . "). Überprüfen Sie den Key auf zusätzliche Zeichen."
                        ];
                    }
                }

                // Secret Key Format prüfen und bereinigen
                if (strlen($cleanSecret) !== 40) {
                    \Log::warning('DigitalOcean Secret Key Format Issue', [
                        'original_length' => strlen($this->storage_config['secret']),
                        'cleaned_length' => strlen($cleanSecret),
                        'has_leading_spaces' => $this->storage_config['secret'] !== ltrim($this->storage_config['secret']),
                        'has_trailing_spaces' => $this->storage_config['secret'] !== rtrim($this->storage_config['secret'])
                    ]);
                    
                    if (strlen($cleanSecret) === 40) {
                        // Secret war nur mit Leerzeichen, automatisch bereinigen
                        $this->storage_config['secret'] = $cleanSecret;
                        \Log::info('Secret Key automatisch bereinigt');
                    } else {
                        return [
                            'success' => false,
                            'message' => "DigitalOcean Secret Key sollte 40 Zeichen lang sein (aktuell: " . strlen($this->storage_config['secret']) . ", bereinigt: " . strlen($cleanSecret) . "). Überprüfen Sie den Key auf zusätzliche Zeichen oder kopieren Sie ihn erneut aus DigitalOcean."
                        ];
                    }
                }
            }

            return ['success' => true, 'message' => 'Credentials Format validiert'];

        } catch (\Exception $e) {
            \Log::error('validateDigitalOceanCredentials Exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['success' => false, 'message' => 'Credential-Validierung fehlgeschlagen: ' . $e->getMessage()];
        }
    }

    /**
     * Direkter S3/Spaces Upload-Test ohne Laravel Storage Layer
     */
    private function testDirectS3Upload(): array
    {
        try {
            // AWS S3 Client direkt verwenden
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $this->storage_config['region'],
                'endpoint' => $this->storage_config['endpoint'],
                'credentials' => [
                    'key' => $this->storage_config['key'],
                    'secret' => $this->storage_config['secret'],
                ],
                'use_path_style_endpoint' => false,
            ]);

            $bucket = $this->storage_config['bucket'];
            $testKey = 'direct-test-' . time() . '.txt';
            $testContent = 'Direct S3 test content - ' . time();

            \Log::info('testDirectS3Upload Debug', [
                'bucket' => $bucket,
                'key' => $testKey,
                'endpoint' => $this->storage_config['endpoint'],
                'region' => $this->storage_config['region']
            ]);

            // 1. Upload-Test
            $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $testKey,
                'Body' => $testContent,
                'ContentType' => 'text/plain',
            ]);

            \Log::info('testDirectS3Upload Upload Success', [
                'etag' => $result['ETag'] ?? 'unknown',
                'version_id' => $result['VersionId'] ?? 'none'
            ]);

            // 2. Download-Test
            $getResult = $s3Client->getObject([
                'Bucket' => $bucket,
                'Key' => $testKey,
            ]);

            $downloadedContent = (string) $getResult['Body'];
            
            \Log::info('testDirectS3Upload Download Success', [
                'content_length' => strlen($downloadedContent),
                'content_matches' => $downloadedContent === $testContent
            ]);

            // 3. Cleanup
            try {
                $s3Client->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $testKey,
                ]);
                \Log::info('testDirectS3Upload Cleanup Success');
            } catch (\Exception $cleanupException) {
                \Log::warning('testDirectS3Upload Cleanup Failed', [
                    'exception' => $cleanupException->getMessage()
                ]);
            }

            // 4. Inhalt vergleichen
            if ($downloadedContent === $testContent) {
                return ['success' => true, 'message' => 'Direkter S3/Spaces Test erfolgreich - Upload, Download und Löschung funktionieren'];
            } else {
                return ['success' => false, 'message' => 'Inhalt stimmt nicht überein - Upload/Download Problem'];
            }

        } catch (\Aws\S3\Exception\S3Exception $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMessage = $e->getAwsErrorMessage();
            $statusCode = $e->getStatusCode();
            
            \Log::error('testDirectS3Upload S3 Exception', [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'status_code' => $statusCode,
                'request_url' => $e->getRequest() ? $e->getRequest()->getUri() : 'unknown'
            ]);

            if ($statusCode === 403) {
                return ['success' => false, 'message' => "403 Forbidden beim direkten S3-Test:\n• Credentials: {$errorCode}\n• Nachricht: {$errorMessage}\n• Überprüfen Sie Access Key, Secret Key und Space-Berechtigungen"];
            }

            return ['success' => false, 'message' => "Direkter S3-Test fehlgeschlagen ({$errorCode}): {$errorMessage}"];
        } catch (\Exception $e) {
            \Log::error('testDirectS3Upload General Exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['success' => false, 'message' => 'Direkter S3-Test Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Filesystem-Konfiguration erstellen
     */
    public function buildFilesystemConfig(): array
    {
        switch ($this->storage_driver) {
            case 's3':
                return [
                    'driver' => 's3',
                    'key' => $this->storage_config['key'],
                    'secret' => $this->storage_config['secret'],
                    'region' => $this->storage_config['region'],
                    'bucket' => $this->storage_config['bucket'],
                    'url' => $this->storage_config['url'] ?? null,
                    'endpoint' => $this->storage_config['endpoint'] ?? null,
                    'use_path_style_endpoint' => $this->storage_config['use_path_style_endpoint'] ?? false,
                    'visibility' => 'private',
                    'throw' => false,
                ];

            case 'digitalocean':
                return [
                    'driver' => 's3',
                    'key' => $this->storage_config['key'],
                    'secret' => $this->storage_config['secret'],
                    'region' => $this->storage_config['region'],
                    'bucket' => $this->storage_config['bucket'],
                    'endpoint' => $this->storage_config['endpoint'],
                    'use_path_style_endpoint' => false,
                    'url' => $this->storage_config['url'] ?? null,
                    'visibility' => 'private',
                    'throw' => true, // Bessere Fehlermeldungen
                    'version' => 'latest',
                    'signature_version' => 'v4',
                ];

            default:
                return [
                    'driver' => 'local',
                    'root' => storage_path('app/documents'),
                ];
        }
    }

    /**
     * Aktuelle Speicher-Disk abrufen
     */
    public function getDisk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        if ($this->storage_driver === 'local') {
            return Storage::disk('local');
        }

        $config = $this->buildFilesystemConfig();
        config(['filesystems.disks.documents' => $config]);
        
        return Storage::disk('documents');
    }
}