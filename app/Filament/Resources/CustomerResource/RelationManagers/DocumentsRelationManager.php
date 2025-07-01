<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use App\Services\DocumentUploadConfig;
use Filament\Resources\RelationManagers\RelationManager;

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
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktueller Customer aus dem Record
        $customer = $this->getOwnerRecord();
        
        // Verwende die neue forClients() Factory-Methode mit dynamischen Pfaden
        return DocumentUploadConfig::forClients($customer)
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
                'emptyStateHeading' => 'Keine Dokumente vorhanden',
                'emptyStateDescription' => 'Fügen Sie das erste Kunden-Dokument hinzu.',

                // Erweiterte Features
                'enableDragDrop' => true,
                'showStats' => true,
                'enableTags' => true,

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
                'showEdit' => true,
                'showDelete' => true,
                'enableBulkActions' => true,
                'enableBulkDelete' => true,

                // Filter
                'enableCategoryFilter' => true,
                'enableDateFilters' => true,
            ]);
    }
}