<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

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
     * Konfiguration für Solaranlagen-Dokumente mit dynamischen Pfaden
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktuelle SolarPlant aus dem Record
        $solarPlant = $this->getOwnerRecord();
        
        // Verwende die forSolarPlants() Factory-Methode mit dynamischen Pfaden
        return DocumentUploadConfig::forSolarPlants($solarPlant);
    }
}