<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use App\Services\DocumentUploadConfig;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Log;

class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Dokumente';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    /**
     * Konfiguration für Kunden-Dokumente mit dynamischen Pfaden
     * Nutzt die DocumentPathSettings aus der Datenbank
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktueller Customer aus dem Record
        $customer = $this->getOwnerRecord();
        
        Log::debug('DocumentsRelationManager: Erstelle Upload-Konfiguration', [
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name,
            'customer_number' => $customer?->customer_number,
            'customer_type' => $customer?->customer_type,
            'method' => 'getDocumentUploadConfig'
        ]);
        
        // Verwende die forClients() Factory-Methode mit dynamischen Pfaden
        // Wichtig: setModel() muss aufgerufen werden, damit DocumentPathSettings funktioniert
        $config = DocumentUploadConfig::forClients()
            ->setModel($customer)
            ->merge([
                // Upload-Einstellungen
                'maxSize' => 10240, // 10MB in KB
                'multiple' => true,
                'acceptedFileTypes' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                ],

                // Kategorien für Kunden-Dokumente
                'categories' => [
                    'contract' => 'Verträge',
                    'invoice' => 'Rechnungen',
                    'offer' => 'Angebote',
                    'correspondence' => 'Korrespondenz',
                    'technical' => 'Technische Unterlagen',
                    'legal' => 'Rechtliche Dokumente',
                    'other' => 'Sonstiges'
                ],
                'categoryColors' => [
                    'contract' => 'success',
                    'invoice' => 'warning',
                    'offer' => 'info',
                    'correspondence' => 'green',
                    'technical' => 'blue',
                    'legal' => 'purple',
                    'other' => 'gray',
                ],
                'categoryRequired' => true,
                'categorySearchable' => true,

                // UI-Einstellungen
                'title' => 'Kunden-Dokumente',
                'createButtonLabel' => 'Dokument hinzufügen',
                'createButtonIcon' => 'heroicon-o-document-plus',
                'emptyStateHeading' => 'Keine Dokumente vorhanden',
                'emptyStateDescription' => 'Fügen Sie das erste Kunden-Dokument hinzu.',
                'emptyStateIcon' => 'heroicon-o-document',
                'enableCreate' => true,

                // Erweiterte Features
                'enableDragDrop' => true,
                'showStats' => false,
                'enableTags' => false,

                // Formular-Einstellungen
                'showDescription' => true,
                'descriptionRows' => 3,
                'autoFillName' => true,

                // Tabellen-Einstellungen
                'showIcon' => true,
                'showCategory' => true,
                'showSize' => true,
                'showUploadedBy' => true,
                'showCreatedAt' => true,
                'categoryBadge' => true,
                'nameSearchable' => true,
                'nameSortable' => true,

                // Aktionen
                'showPreview' => true,
                'showDownload' => true,
                'showView' => true,
                'showEdit' => true,
                'showDelete' => true,
                'groupActions' => true,
                'enableBulkActions' => true,
                'enableBulkDelete' => true,

                // Filter
                'enableCategoryFilter' => true,
                'enableDateFilters' => true,

                // Modal-Einstellungen
                'modalWidth' => '4xl',

                // Labels
                'actionsLabel' => 'Aktionen',
                'previewLabel' => 'Vorschau',
                'downloadLabel' => 'Herunterladen',
                'viewLabel' => 'Anzeigen',
                'editLabel' => 'Bearbeiten',
                'deleteLabel' => 'Löschen',
            ]);

        Log::debug('DocumentsRelationManager: Upload-Konfiguration erstellt', [
            'customer_id' => $customer?->id,
            'storage_directory' => $config->getStorageDirectory(),
            'disk_name' => $config->getDiskName(),
            'path_preview' => $config->previewPath(),
            'categories_count' => count($config->get('categories', [])),
            'max_size_kb' => $config->get('maxSize'),
            'accepted_file_types_count' => count($config->get('acceptedFileTypes', []))
        ]);

        return $config;
    }
}