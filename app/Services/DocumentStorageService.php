<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    /**
     * Aktuelle Storage-Disk basierend auf den Admin-Einstellungen abrufen
     */
    public static function getDisk(): Filesystem
    {
        $setting = StorageSetting::current();
        
        if ($setting) {
            return $setting->getDisk();
        }
        
        // Fallback auf lokalen Speicher
        return Storage::disk('local');
    }
    
    /**
     * Disk-Name für FileUpload-Komponenten abrufen
     */
    public static function getDiskName(): string
    {
        $setting = StorageSetting::current();
        
        if ($setting && $setting->storage_driver !== 'local') {
            return 'documents'; // Dynamische Disk aus StorageSetting
        }
        
        return 'local'; // Fallback
    }
    
    /**
     * Dokument-Metadaten aus hochgeladener Datei extrahieren
     */
    public static function extractFileMetadata(string $filePath, ?string $diskName = null): array
    {
        $diskName = $diskName ?? self::getDiskName();
        $disk = $diskName === 'documents' ? self::getDisk() : Storage::disk($diskName);
        
        $metadata = [
            'disk' => $diskName,
            'path' => $filePath,
            'original_name' => basename($filePath),
            'size' => 0,
            'mime_type' => 'application/octet-stream',
            'uploaded_by' => auth()->id(),
        ];
        
        try {
            if ($disk->exists($filePath)) {
                // Dateigröße ermitteln
                $metadata['size'] = $disk->size($filePath);
                
                // MIME-Type ermitteln
                if ($diskName === 'local') {
                    // Für lokale Dateien: Versuche mime_content_type
                    $fullPath = storage_path('app/' . $filePath);
                    if (file_exists($fullPath)) {
                        $mimeType = mime_content_type($fullPath);
                        if ($mimeType && $mimeType !== 'application/octet-stream') {
                            $metadata['mime_type'] = $mimeType;
                        } else {
                            $metadata['mime_type'] = self::getMimeTypeFromExtension($filePath);
                        }
                    } else {
                        $metadata['mime_type'] = self::getMimeTypeFromExtension($filePath);
                    }
                } else {
                    // Für Cloud-Storage: MIME-Type aus Dateierweiterung ableiten
                    $metadata['mime_type'] = self::getMimeTypeFromExtension($filePath);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('DocumentStorageService: Fehler beim Extrahieren der Metadaten', [
                'file_path' => $filePath,
                'disk' => $diskName,
                'error' => $e->getMessage()
            ]);
        }
        
        return $metadata;
    }
    
    /**
     * MIME-Type basierend auf Dateierweiterung ermitteln
     */
    private static function getMimeTypeFromExtension(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            default => 'application/octet-stream'
        };
    }
    
    /**
     * Standard-Verzeichnis für Dokument-Uploads basierend auf Kontext
     * @deprecated Verwende stattdessen getUploadDirectoryForModel()
     */
    public static function getUploadDirectory(?string $context = null, ?array $contextData = null): string
    {
        if ($context === 'supplier_contract_billing' && $contextData) {
            $contractNumber = $contextData['contract_number'] ?? 'unbekannt';
            $contractNumber = \Illuminate\Support\Str::slug($contractNumber);
            return "Lieferanten-Energieversorger-Vertraege-{$contractNumber}-Abrechnungen";
        }
        
        if ($context === 'supplier_contract') {
            return 'Lieferanten-Energieversorger-Vertraege';
        }
        
        if ($context === 'customer') {
            return 'Kunden-Dokumente';
        }
        
        return 'documents';
    }

    /**
     * Dynamisches Upload-Verzeichnis basierend auf Model und Storage-Pfad-Konfiguration
     */
    public static function getUploadDirectoryForModel(string $type, $model = null, array $additionalData = []): string
    {
        // Zuerst versuchen wir DocumentPathSetting zu verwenden
        if ($model) {
            $documentableType = get_class($model);
            $category = $additionalData['category'] ?? null;
            
            $pathSetting = \App\Models\DocumentPathSetting::getPathConfig($documentableType, $category);
            if ($pathSetting) {
                return $pathSetting->generatePath($model, $additionalData);
            }
        }
        
        // Auch ohne Model versuchen wir DocumentPathSetting für den Typ
        if ($type === 'supplier_contract_billings' && !$model) {
            // Fallback für SupplierContractBilling ohne Model
            $pathSetting = \App\Models\DocumentPathSetting::getPathConfig('App\Models\SupplierContractBilling', $additionalData['category'] ?? null);
            if ($pathSetting) {
                // Verwende Dummy-Daten für Platzhalter wenn kein Model vorhanden
                $dummyData = array_merge([
                    'supplier_number' => 'SUPPLIER',
                    'contract_number' => 'CONTRACT',
                    'billing_period' => 'YYYY-MM',
                ], $additionalData);
                return $pathSetting->generatePath(null, $dummyData);
            }
        }
        
        // Fallback auf StorageSetting
        $setting = StorageSetting::current();
        
        if ($setting) {
            return $setting->resolvePath($type, $model, $additionalData);
        }
        
        // Fallback auf statische Pfade
        return self::getFallbackDirectory($type);
    }

    /**
     * Fallback-Verzeichnisse wenn keine Storage-Pfad-Konfiguration vorhanden
     */
    private static function getFallbackDirectory(string $type): string
    {
        return match($type) {
            'suppliers' => 'supplier-documents',
            'contracts' => 'contract-documents',
            'supplier_contracts' => 'supplier-contract-documents',
            'supplier_contract_billings' => 'supplier-contract-billing-documents',
            'solar_plants' => 'solar-plant-documents',
            'tasks' => 'task-documents',
            'general' => 'documents',
            default => $type . '-documents'
        };
    }

    /**
     * Verfügbare Pfad-Typen abrufen
     */
    public static function getAvailablePathTypes(): array
    {
        return [
            'suppliers' => 'Lieferanten-Dokumente',
            'contracts' => 'Vertrags-Dokumente',
            'supplier_contracts' => 'Lieferantenvertrags-Dokumente',
            'solar_plants' => 'Solaranlagen-Dokumente',
            'tasks' => 'Aufgaben-Dokumente',
            'general' => 'Allgemeine Dokumente',
        ];
    }

    /**
     * Pfad-Vorschau für einen bestimmten Typ und Model generieren
     */
    public static function previewPath(string $type, $model = null, array $additionalData = []): array
    {
        // Zuerst versuchen wir DocumentPathSetting zu verwenden
        if ($model) {
            $documentableType = get_class($model);
            $category = $additionalData['category'] ?? null;
            
            $pathSetting = \App\Models\DocumentPathSetting::getPathConfig($documentableType, $category);
            if ($pathSetting) {
                $resolvedPath = $pathSetting->generatePath($model, $additionalData);
                $template = $pathSetting->path_template;
                
                // Verwendete Platzhalter ermitteln
                $placeholdersUsed = [];
                $availablePlaceholders = \App\Models\DocumentPathSetting::getAvailablePlaceholders($documentableType);
                
                foreach ($availablePlaceholders as $placeholder => $description) {
                    if (strpos($template, '{' . $placeholder . '}') !== false) {
                        $placeholdersUsed[$placeholder] = $description;
                    }
                }
                
                return [
                    'resolved_path' => $resolvedPath,
                    'template' => $template,
                    'placeholders_used' => $placeholdersUsed,
                    'is_fallback' => false,
                    'path_config' => [
                        'documentable_type' => $pathSetting->documentable_type,
                        'category' => $pathSetting->category,
                        'description' => $pathSetting->description,
                    ]
                ];
            }
        }
        
        // Auch ohne Model versuchen wir DocumentPathSetting für den Typ
        if ($type === 'supplier_contract_billings') {
            $pathSetting = \App\Models\DocumentPathSetting::getPathConfig('App\Models\SupplierContractBilling', $additionalData['category'] ?? null);
            if ($pathSetting) {
                $dummyData = array_merge([
                    'supplier_number' => 'SUPPLIER',
                    'contract_number' => 'CONTRACT',
                    'billing_period' => 'YYYY-MM',
                ], $additionalData);
                
                $resolvedPath = $pathSetting->generatePath(null, $dummyData);
                $template = $pathSetting->path_template;
                
                // Verwendete Platzhalter ermitteln
                $placeholdersUsed = [];
                $availablePlaceholders = \App\Models\DocumentPathSetting::getAvailablePlaceholders('App\Models\SupplierContractBilling');
                
                foreach ($availablePlaceholders as $placeholder => $description) {
                    if (strpos($template, '{' . $placeholder . '}') !== false) {
                        $placeholdersUsed[$placeholder] = $description;
                    }
                }
                
                return [
                    'resolved_path' => $resolvedPath,
                    'template' => $template,
                    'placeholders_used' => $placeholdersUsed,
                    'is_fallback' => false,
                    'path_config' => [
                        'documentable_type' => $pathSetting->documentable_type,
                        'category' => $pathSetting->category,
                        'description' => $pathSetting->description,
                    ]
                ];
            }
        }
        
        // Fallback auf StorageSetting
        $setting = StorageSetting::current();
        
        if (!$setting) {
            return [
                'resolved_path' => self::getFallbackDirectory($type),
                'template' => 'Keine Storage-Pfad-Konfiguration vorhanden',
                'placeholders_used' => [],
                'is_fallback' => true
            ];
        }

        $pathConfig = $setting->getStoragePath($type);
        
        if (!$pathConfig) {
            return [
                'resolved_path' => self::getFallbackDirectory($type),
                'template' => 'Kein Pfad für Typ "' . $type . '" konfiguriert',
                'placeholders_used' => [],
                'is_fallback' => true
            ];
        }

        $template = $pathConfig['path'] ?? $type . '-documents';
        $resolvedPath = $setting->resolvePath($type, $model, $additionalData);
        
        // Verwendete Platzhalter ermitteln
        $placeholdersUsed = [];
        $availablePlaceholders = $setting->getAvailablePlaceholders($type);
        
        foreach ($availablePlaceholders as $placeholder => $description) {
            if (strpos($template, '{' . $placeholder . '}') !== false) {
                $placeholdersUsed[$placeholder] = $description;
            }
        }

        return [
            'resolved_path' => $resolvedPath,
            'template' => $template,
            'placeholders_used' => $placeholdersUsed,
            'is_fallback' => false,
            'path_config' => $pathConfig
        ];
    }

    /**
     * Vollständigen Upload-Pfad inklusive Dateiname generieren
     */
    public static function generateFullUploadPath(string $type, string $filename, $model = null, array $additionalData = []): string
    {
        $directory = self::getUploadDirectoryForModel($type, $model, $additionalData);
        
        // Dateiname bereinigen
        $cleanFilename = self::sanitizeFilename($filename);
        
        return $directory . '/' . $cleanFilename;
    }

    /**
     * Dateiname für Upload bereinigen
     */
    private static function sanitizeFilename(string $filename): string
    {
        // Dateiname und Erweiterung trennen
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['filename'] ?? 'document';
        $extension = $pathInfo['extension'] ?? '';
        
        // Name bereinigen
        $cleanName = preg_replace('/[^\w\-_.]/', '-', $name);
        $cleanName = preg_replace('/-+/', '-', $cleanName);
        $cleanName = trim($cleanName, '-');
        
        if (empty($cleanName)) {
            $cleanName = 'document';
        }
        
        // Zusammensetzen
        return $extension ? $cleanName . '.' . $extension : $cleanName;
    }

    /**
     * Test-Pfad-Generierung für Debugging
     */
    public static function testPathGeneration(): array
    {
        $results = [];
        $types = ['suppliers', 'contracts', 'general'];
        
        foreach ($types as $type) {
            $results[$type] = [
                'without_model' => self::previewPath($type),
                'with_test_data' => self::previewPath($type, null, [
                    'supplier_number' => 'SUP-001',
                    'supplier_name' => 'Test Lieferant GmbH',
                    'document_number' => 'DOC-123',
                    'document_name' => 'Testdokument'
                ])
            ];
        }
        
        return $results;
    }
}
