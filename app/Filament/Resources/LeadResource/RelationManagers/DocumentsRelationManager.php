<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

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

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->documents()->count();
        return $count > 0 ? (string) $count : null;
    }

    /**
     * Konfiguration für Lead-Dokumente mit dynamischen Pfaden
     * Nutzt die DocumentPathSettings aus der Datenbank
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktueller Lead aus dem Record
        $lead = $this->getOwnerRecord();
        
        Log::debug('Lead DocumentsRelationManager: Erstelle Upload-Konfiguration', [
            'lead_id' => $lead?->id,
            'lead_name' => $lead?->name,
            'lead_number' => $lead?->customer_number,
            'customer_type' => $lead?->customer_type,
            'method' => 'getDocumentUploadConfig'
        ]);
        
        // Verwende die forClients() Factory-Methode mit dynamischen Pfaden
        // Wichtig: setModel() muss aufgerufen werden, damit DocumentPathSettings funktioniert
        $config = DocumentUploadConfig::forClients()
            ->setModel($lead)
            ->merge([
                // Upload-Einstellungen
                'maxSize' => 51200, // 50MB in KB
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

                // Kategorien für Lead-Dokumente
                'categories' => [
                    'initial_contact' => 'Erstkontakt',
                    'offer' => 'Angebote',
                    'presentation' => 'Präsentationen',
                    'correspondence' => 'Korrespondenz',
                    'technical' => 'Technische Unterlagen',
                    'qualification' => 'Qualifizierung',
                    'contract_draft' => 'Vertragsentwurf',
                    'other' => 'Sonstiges'
                ],
                'categoryColors' => [
                    'initial_contact' => 'info',
                    'offer' => 'warning',
                    'presentation' => 'success',
                    'correspondence' => 'green',
                    'technical' => 'blue',
                    'qualification' => 'purple',
                    'contract_draft' => 'orange',
                    'other' => 'gray',
                ],
                'categoryRequired' => true,
                'categorySearchable' => true,

                // UI-Einstellungen
                'title' => 'Lead-Dokumente',
                'createButtonLabel' => 'Dokument hinzufügen',
                'createButtonIcon' => 'heroicon-o-document-plus',
                'emptyStateHeading' => 'Keine Dokumente vorhanden',
                'emptyStateDescription' => 'Fügen Sie das erste Lead-Dokument hinzu.',
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

        Log::debug('Lead DocumentsRelationManager: Upload-Konfiguration erstellt', [
            'lead_id' => $lead?->id,
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
