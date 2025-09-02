<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class DigitalOceanSpacesService
{
    private $s3Client;
    private $bucket;
    private $storageSetting;
    private $config;

    public function __construct()
    {
        // Aktuelle Storage-Einstellungen laden
        $this->storageSetting = StorageSetting::current();
        
        if (!$this->storageSetting || $this->storageSetting->storage_driver !== 'digitalocean') {
            throw new \Exception('Keine DigitalOcean Spaces Konfiguration gefunden. Bitte konfigurieren Sie die Speicher-Einstellungen unter Admin → Speicher-Einstellungen.');
        }

        // Konfiguration aus StorageSetting verwenden
        $this->config = $this->storageSetting->storage_config;
        $this->bucket = $this->config['bucket'];

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->config['region'],
            'endpoint' => $this->config['endpoint'],
            'use_path_style_endpoint' => false,
            'credentials' => [
                'key' => $this->config['key'],
                'secret' => $this->config['secret'],
            ],
        ]);
    }

    /**
     * List all files and directories in the given path
     */
    public function listContents(string $path = '', bool $recursive = false): array
    {
        try {
            $prefix = $path ? rtrim($path, '/') . '/' : '';
            
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'Delimiter' => $recursive ? '' : '/',
            ]);

            $items = [];
            
            // Add directories (common prefixes)
            if (!$recursive && isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $commonPrefix) {
                    $dirPath = rtrim($commonPrefix['Prefix'], '/');
                    $dirName = basename($dirPath);
                    
                    $items[] = [
                        'type' => 'directory',
                        'name' => $dirName,
                        'path' => $dirPath,
                        'size' => 0,
                        'lastModified' => null,
                        'url' => null,
                    ];
                }
            }
            
            // Add files
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    // Skip if it's the prefix itself (directory marker)
                    if ($object['Key'] === $prefix) {
                        continue;
                    }
                    
                    // If not recursive, skip nested files
                    if (!$recursive && substr_count($object['Key'], '/') > substr_count($prefix, '/')) {
                        continue;
                    }
                    
                    $fileName = basename($object['Key']);
                    
                    // Skip empty file names (directory markers)
                    if (empty($fileName)) {
                        continue;
                    }
                    
                    $items[] = [
                        'type' => 'file',
                        'name' => $fileName,
                        'path' => $object['Key'],
                        'size' => $object['Size'],
                        'lastModified' => $object['LastModified'],
                        'url' => $this->getFileUrl($object['Key']),
                        'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                        'mimeType' => $this->getMimeType($fileName),
                    ];
                }
            }
            
            // Sort items: directories first, then files
            usort($items, function ($a, $b) {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'directory' ? -1 : 1;
                }
                return strcasecmp($a['name'], $b['name']);
            });
            
            return $items;
            
        } catch (AwsException $e) {
            throw new \Exception('Error listing DigitalOcean Spaces contents: ' . $e->getMessage());
        }
    }

    /**
     * Get the public URL for a file
     */
    public function getFileUrl(string $filePath): string
    {
        // Wenn eine CDN URL konfiguriert ist, diese verwenden
        if (!empty($this->config['url'])) {
            return rtrim($this->config['url'], '/') . '/' . ltrim($filePath, '/');
        }
        
        // Ansonsten den direkten Endpoint verwenden
        return rtrim($this->config['endpoint'], '/') . '/' . 
               $this->config['bucket'] . '/' . 
               ltrim($filePath, '/');
    }

    /**
     * Get MIME type based on file extension
     */
    private function getMimeType(string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'xml' => 'application/xml',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Get file info by path
     */
    public function getFileInfo(string $filePath): ?array
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $filePath,
            ]);
            
            return [
                'name' => basename($filePath),
                'path' => $filePath,
                'size' => $result['ContentLength'],
                'lastModified' => $result['LastModified'],
                'contentType' => $result['ContentType'] ?? $this->getMimeType(basename($filePath)),
                'url' => $this->getFileUrl($filePath),
            ];
            
        } catch (AwsException $e) {
            return null;
        }
    }

    /**
     * Format file size in human readable format
     */
    public static function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Get breadcrumb navigation for a path
     */
    public static function getBreadcrumbs(string $path): array
    {
        $breadcrumbs = [
            [
                'name' => 'Root',
                'path' => '',
            ]
        ];
        
        if (empty($path)) {
            return $breadcrumbs;
        }
        
        $pathParts = explode('/', trim($path, '/'));
        $currentPath = '';
        
        foreach ($pathParts as $part) {
            $currentPath .= ($currentPath ? '/' : '') . $part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $currentPath,
            ];
        }
        
        return $breadcrumbs;
    }

    /**
     * Check if DigitalOcean Spaces is properly configured
     */
    public static function isConfigured(): bool
    {
        try {
            $storageSetting = StorageSetting::current();
            
            if (!$storageSetting || $storageSetting->storage_driver !== 'digitalocean') {
                return false;
            }

            $config = $storageSetting->storage_config;
            
            // Check if all required configuration keys are present and not empty
            $requiredKeys = ['key', 'secret', 'region', 'bucket', 'endpoint'];
            
            foreach ($requiredKeys as $key) {
                if (empty($config[$key])) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed configuration status information
     */
    public static function getConfigurationStatus(): array
    {
        try {
            $storageSetting = StorageSetting::current();
            
            if (!$storageSetting) {
                return [
                    'configured' => false,
                    'error' => 'Keine Speicher-Einstellungen gefunden.',
                    'message' => 'Bitte konfigurieren Sie die Speicher-Einstellungen unter Admin → Speicher-Einstellungen.',
                    'missing' => ['all'],
                ];
            }

            if ($storageSetting->storage_driver !== 'digitalocean') {
                return [
                    'configured' => false,
                    'error' => 'DigitalOcean Spaces ist nicht als Speicher-Treiber ausgewählt.',
                    'message' => 'Aktueller Treiber: ' . ($storageSetting->storage_driver ?? 'nicht gesetzt'),
                    'current_driver' => $storageSetting->storage_driver,
                ];
            }

            $config = $storageSetting->storage_config;
            $requiredKeys = [
                'key' => 'Access Key',
                'secret' => 'Secret Key', 
                'region' => 'Region',
                'bucket' => 'Bucket Name',
                'endpoint' => 'Endpoint URL'
            ];
            
            $missing = [];
            foreach ($requiredKeys as $key => $label) {
                if (empty($config[$key])) {
                    $missing[] = $label;
                }
            }
            
            if (!empty($missing)) {
                return [
                    'configured' => false,
                    'error' => 'Fehlende DigitalOcean Spaces Konfiguration.',
                    'message' => 'Fehlende Einstellungen: ' . implode(', ', $missing),
                    'missing' => $missing,
                ];
            }
            
            return [
                'configured' => true,
                'message' => 'DigitalOcean Spaces ist korrekt konfiguriert.',
                'bucket' => $config['bucket'],
                'region' => $config['region'],
                'endpoint' => $config['endpoint'],
                'has_cdn_url' => !empty($config['url']),
                'cdn_url' => $config['url'] ?? null,
            ];
            
        } catch (\Exception $e) {
            return [
                'configured' => false,
                'error' => 'Fehler beim Überprüfen der Konfiguration.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test the connection to DigitalOcean Spaces
     */
    public function testConnection(): array
    {
        try {
            // Try to list the bucket to test connection
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'MaxKeys' => 1,
            ]);
            
            return [
                'success' => true,
                'message' => 'Verbindung zu DigitalOcean Spaces erfolgreich.',
                'bucket' => $this->bucket,
                'object_count' => $result['KeyCount'] ?? 0,
            ];
            
        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => 'Verbindung zu DigitalOcean Spaces fehlgeschlagen.',
                'message' => $e->getMessage(),
                'aws_error_code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Unerwarteter Fehler beim Testen der Verbindung.',
                'message' => $e->getMessage(),
            ];
        }
    }
}
