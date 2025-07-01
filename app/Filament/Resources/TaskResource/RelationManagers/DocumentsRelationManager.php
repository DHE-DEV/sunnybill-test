<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

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
     * Konfiguration für Aufgaben-Dokumente mit dynamischen Pfaden
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktuelle Task aus dem Record
        $task = $this->getOwnerRecord();
        
        // Verwende die forTasks() Factory-Methode mit dynamischen Pfaden
        return DocumentUploadConfig::forTasks($task);
    }
}