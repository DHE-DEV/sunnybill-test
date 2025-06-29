<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

        // Debug: Konfiguration loggen
        \Log::info('StorageSetting validateConfig Debug', [
            'driver' => $this->storage_driver,
            'config' => $this->storage_config
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
            
            // Temporäre Disk-Konfiguration erstellen
            config(['filesystems.disks.test_storage' => $config]);
            
            $disk = Storage::disk('test_storage');
            
            // Test-Datei erstellen
            $testContent = 'test-connection-' . time();
            $testFile = 'sunnybill-test/test-connection-' . time() . '.txt';
            
            // Schritt 1: Datei hochladen
            $uploadResult = $disk->put($testFile, $testContent);
            if (!$uploadResult) {
                return ['success' => false, 'message' => 'Datei konnte nicht hochgeladen werden'];
            }
            
            // Schritt 2: Prüfen ob Datei existiert
            if (!$disk->exists($testFile)) {
                return ['success' => false, 'message' => 'Hochgeladene Datei wurde nicht gefunden'];
            }
            
            // Schritt 3: Datei lesen
            $content = $disk->get($testFile);
            
            // Schritt 4: Test-Datei löschen
            $disk->delete($testFile);
            
            // Schritt 5: Inhalt vergleichen
            if ($content === $testContent) {
                return ['success' => true, 'message' => 'Verbindung erfolgreich getestet - Upload, Download und Löschung funktionieren'];
            } else {
                return ['success' => false, 'message' => 'Datei konnte nicht korrekt gelesen werden (Inhalt stimmt nicht überein)'];
            }
            
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMessage = $e->getAwsErrorMessage();
            return ['success' => false, 'message' => "S3/Spaces Fehler ({$errorCode}): {$errorMessage}"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Verbindungsfehler: ' . $e->getMessage()];
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
                    'throw' => false,
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