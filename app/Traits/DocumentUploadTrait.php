<?php

namespace App\Traits;

use App\Services\DocumentStorageService;
use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Livewire\Component as Livewire;

/**
 * Trait für wiederverwendbare Dokumenten-Upload-Funktionalität
 * 
 * Verwendung in RelationManager:
 * 
 * class MyDocumentsRelationManager extends RelationManager
 * {
 *     use DocumentUploadTrait;
 *     
 *     protected static string $relationship = 'documents';
 *     
 *     protected function getDocumentUploadConfig(): array
 *     {
 *         return [
 *             'directory' => 'my-documents',
 *             'categories' => ['contract', 'invoice'],
 *             'maxSize' => 10240, // 10MB
 *             'acceptedFileTypes' => ['application/pdf'],
 *         ];
 *     }
 * }
 */
trait DocumentUploadTrait
{
    /**
     * Standard-Konfiguration für Dokumenten-Upload
     */
    protected function getDocumentUploadConfig(): array
    {
        return [
            'directory' => 'documents',
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'manual' => 'Handbuch',
                'photo' => 'Foto',
                'plan' => 'Plan/Zeichnung',
                'report' => 'Bericht',
                'correspondence' => 'Korrespondenz',
                'other' => 'Sonstiges',
            ],
            'maxSize' => 10240, // 10MB
            'acceptedFileTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'application/zip',
                'application/x-rar-compressed',
            ],
            'preserveFilenames' => false, // Geändert zu false für Zeitstempel-Naming
            'timestampFilenames' => true, // Neue Option für automatische Zeitstempel
            'multiple' => false,
            'formColumns' => 2,
            'modalWidth' => '4xl',
            'showPreview' => true,
            'showDownload' => true,
            'showFileInfo' => true,
            'enableBulkActions' => true,
            'defaultSort' => ['created_at', 'desc'],
            'emptyStateHeading' => 'Keine Dokumente vorhanden',
            'emptyStateDescription' => 'Fügen Sie das erste Dokument hinzu.',
            'createButtonLabel' => 'Dokument hinzufügen',
            'enableCreate' => true, // Explizit aktivieren
        ];
    }

    /**
     * Erstellt das Formular mit der konfigurierten Einstellung
     */
    public function form(Form $form): Form
    {
        Log::debug('DocumentUploadTrait: Erstelle Formular', [
            'trait_class' => static::class,
            'relationship' => property_exists($this, 'relationship') ? static::$relationship : 'unbekannt'
        ]);

        $config = $this->getDocumentUploadConfig();
        
        Log::debug('DocumentUploadTrait: Konfiguration erhalten', [
            'config_type' => is_object($config) ? get_class($config) : gettype($config),
            'is_object' => is_object($config),
            'has_toArray' => is_object($config) && method_exists($config, 'toArray'),
            'has_getStorageDirectory' => is_object($config) && method_exists($config, 'getStorageDirectory'),
            'has_getDiskName' => is_object($config) && method_exists($config, 'getDiskName')
        ]);
        
        // Konvertiere DocumentUploadConfig zu Array falls nötig, aber behalte wichtige Properties
        if (is_object($config) && method_exists($config, 'toArray')) {
            $configArray = $config->toArray();
            
            // Füge statische Properties hinzu
            if (method_exists($config, 'getDiskName')) {
                $configArray['diskName'] = $config->getDiskName();
            }
            
            // WICHTIG: Füge alle wichtigen Properties für dynamische Pfade hinzu
            // ABER löse storageDirectory NICHT statisch auf - das macht der FormBuilder dynamisch
            if (method_exists($config, 'get')) {
                if ($config->get('pathType')) {
                    $configArray['pathType'] = $config->get('pathType');
                }
                if ($config->get('model')) {
                    $configArray['model'] = $config->get('model');
                }
                if ($config->get('additionalData')) {
                    $configArray['additionalData'] = $config->get('additionalData');
                }
            }
            
            Log::debug('DocumentUploadTrait: Konfiguration konvertiert', [
                'original_config_keys' => is_object($config) ? array_keys($config->toArray()) : [],
                'final_config_keys' => array_keys($configArray),
                'disk_name' => $configArray['diskName'] ?? 'nicht gesetzt',
                'path_type' => $configArray['pathType'] ?? 'nicht gesetzt',
                'has_model' => isset($configArray['model']) ? 'ja' : 'nein',
                'model_class' => isset($configArray['model']) ? get_class($configArray['model']) : 'nicht gesetzt',
                'note' => 'storageDirectory wird dynamisch vom FormBuilder aufgelöst'
            ]);
            
            $config = $configArray;
        }
        
        $formBuilder = DocumentFormBuilder::make($config);
        Log::debug('DocumentUploadTrait: FormBuilder erstellt', [
            'builder_class' => get_class($formBuilder)
        ]);
        
        return $formBuilder->build($form);
    }

    /**
     * Erstellt die Tabelle mit der konfigurierten Einstellung
     * Aktiviert automatisch headerActions auch im View-Modus
     */
    public function table(Table $table): Table
    {
        Log::debug('DocumentUploadTrait: Erstelle Tabelle', [
            'trait_class' => static::class,
            'relationship' => property_exists($this, 'relationship') ? static::$relationship : 'unbekannt'
        ]);

        $config = $this->getDocumentUploadConfig();
        
        Log::debug('DocumentUploadTrait: Tabellen-Konfiguration erhalten', [
            'config_type' => is_object($config) ? get_class($config) : gettype($config),
            'is_object' => is_object($config),
            'has_toArray' => is_object($config) && method_exists($config, 'toArray')
        ]);
        
        // Konvertiere DocumentUploadConfig zu Array falls nötig
        if (is_object($config) && method_exists($config, 'toArray')) {
            $originalConfig = $config;
            $config = $config->toArray();
            
            Log::debug('DocumentUploadTrait: Tabellen-Konfiguration konvertiert', [
                'config_keys' => array_keys($config),
                'title' => $config['title'] ?? 'nicht gesetzt',
                'show_icon' => $config['showIcon'] ?? false,
                'show_category' => $config['showCategory'] ?? false,
                'enable_create' => $config['enableCreate'] ?? false
            ]);
        }
        
        $tableBuilder = DocumentTableBuilder::make($config);
        Log::debug('DocumentUploadTrait: TableBuilder erstellt', [
            'builder_class' => get_class($tableBuilder)
        ]);
        
        return $tableBuilder->build($table);
    }

    /**
     * Stelle sicher, dass Create-Aktionen auch im View-Modus angezeigt werden
     * Berechtigungsprüfung für Team Manager, Administrator und Superadmin
     */
    public function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Team Manager'])->exists() ?? false;
    }

    /**
     * Berechtigungsprüfung für das Bearbeiten von Dokumenten
     */
    public function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Team Manager'])->exists() ?? false;
    }

    /**
     * Berechtigungsprüfung für das Löschen von Dokumenten
     */
    public function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Team Manager'])->exists() ?? false;
    }

    /**
     * Berechtigungsprüfung für das Anzeigen von Dokumenten
     */
    public function canView($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Team Manager'])->exists() ?? false;
    }

    /**
     * Mutiert die Formulardaten vor dem Erstellen
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->processDocumentUploadData($data);
    }

    /**
     * Mutiert die Formulardaten vor dem Aktualisieren
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->processDocumentUploadData($data);
    }

    /**
     * Verarbeitet die Upload-Daten und extrahiert Metadaten
     */
    protected function processDocumentUploadData(array $data): array
    {
        Log::debug('DocumentUploadTrait: Verarbeite Upload-Daten', [
            'data_keys' => array_keys($data),
            'has_path' => isset($data['path']),
            'path_type' => isset($data['path']) ? gettype($data['path']) : 'nicht gesetzt',
            'path_value' => isset($data['path']) ? (is_array($data['path']) ? 'Array mit ' . count($data['path']) . ' Elementen' : $data['path']) : 'nicht gesetzt'
        ]);

        if (isset($data['path']) && $data['path']) {
            // FileUpload gibt ein Array zurück, nimm die erste Datei
            $filePath = is_array($data['path']) ? $data['path'][0] ?? null : $data['path'];
            
            Log::debug('DocumentUploadTrait: Dateipfad extrahiert', [
                'original_path' => $data['path'],
                'extracted_file_path' => $filePath,
                'is_array' => is_array($data['path'])
            ]);
            
            if ($filePath) {
                try {
                    Log::debug('DocumentUploadTrait: Extrahiere Metadaten', [
                        'file_path' => $filePath
                    ]);

                    $metadata = DocumentStorageService::extractFileMetadata($filePath);
                    
                    Log::debug('DocumentUploadTrait: Metadaten extrahiert', [
                        'file_path' => $filePath,
                        'metadata_keys' => array_keys($metadata),
                        'size' => $metadata['size'] ?? 'unbekannt',
                        'mime_type' => $metadata['mime_type'] ?? 'unbekannt',
                        'disk' => $metadata['disk'] ?? 'unbekannt'
                    ]);
                    
                    // Merge Metadaten, aber behalte den ursprünglichen path
                    $originalData = $data;
                    $data = array_merge($data, $metadata);
                    $data['path'] = $filePath; // Stelle sicher, dass path ein String ist
                    
                    Log::debug('DocumentUploadTrait: Daten zusammengeführt', [
                        'original_data_keys' => array_keys($originalData),
                        'final_data_keys' => array_keys($data),
                        'final_path' => $data['path']
                    ]);
                } catch (\Exception $e) {
                    Log::error('DocumentUploadTrait: Fehler beim Extrahieren der Metadaten', [
                        'file_path' => $filePath,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        Log::debug('DocumentUploadTrait: Upload-Daten verarbeitet', [
            'final_data_keys' => array_keys($data),
            'has_final_path' => isset($data['path']),
            'final_path' => $data['path'] ?? 'nicht gesetzt'
        ]);

        return $data;
    }

    /**
     * Hilfsmethode für Custom-Konfiguration
     */
    protected function mergeDocumentConfig(array $customConfig): array
    {
        return array_merge($this->getDocumentUploadConfig(), $customConfig);
    }
}
