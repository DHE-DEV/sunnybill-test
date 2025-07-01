<?php

namespace App\Traits;

use App\Services\DocumentStorageService;
use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use Filament\Forms\Form;
use Filament\Tables\Table;

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
        ];
    }

    /**
     * Erstellt das Formular mit der konfigurierten Einstellung
     */
    public function form(Form $form): Form
    {
        $config = $this->getDocumentUploadConfig();
        
        // Konvertiere DocumentUploadConfig zu Array falls nötig, aber behalte wichtige Properties
        if (is_object($config) && method_exists($config, 'toArray')) {
            $configArray = $config->toArray();
            
            // Füge dynamische Properties hinzu, die nicht im toArray() enthalten sind
            if (method_exists($config, 'getStorageDirectory')) {
                $configArray['storageDirectory'] = $config->getStorageDirectory();
            }
            if (method_exists($config, 'getDiskName')) {
                $configArray['diskName'] = $config->getDiskName();
            }
            
            $config = $configArray;
        }
        
        return DocumentFormBuilder::make($config)->build($form);
    }

    /**
     * Erstellt die Tabelle mit der konfigurierten Einstellung
     */
    public function table(Table $table): Table
    {
        $config = $this->getDocumentUploadConfig();
        
        // Konvertiere DocumentUploadConfig zu Array falls nötig
        if (is_object($config) && method_exists($config, 'toArray')) {
            $config = $config->toArray();
        }
        
        return DocumentTableBuilder::make($config)->build($table);
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
        if (isset($data['path']) && $data['path']) {
            // FileUpload gibt ein Array zurück, nimm die erste Datei
            $filePath = is_array($data['path']) ? $data['path'][0] ?? null : $data['path'];
            
            if ($filePath) {
                try {
                    $metadata = DocumentStorageService::extractFileMetadata($filePath);
                    
                    // Merge Metadaten, aber behalte den ursprünglichen path
                    $data = array_merge($data, $metadata);
                    $data['path'] = $filePath; // Stelle sicher, dass path ein String ist
                } catch (\Exception $e) {
                    \Log::error('Fehler beim Extrahieren der Metadaten in DocumentUploadTrait', [
                        'file_path' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

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