<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use App\Services\DocumentUploadConfig;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

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
     * Konfiguration für Solaranlagen-Dokumente mit dynamischen Pfaden
     */
    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        // Aktuelle SolarPlant aus dem Record
        $solarPlant = $this->getOwnerRecord();
        
        // Verwende die forSolarPlants() Factory-Methode mit dynamischen Pfaden
        return DocumentUploadConfig::forSolarPlants($solarPlant);
    }

    /**
     * Überschreibe die table Methode um sicherzustellen, dass Aktionen auch im View-Modus verfügbar sind
     */
    public function table(Table $table): Table
    {
        // Verwende das DocumentUploadTrait für die Basis-Tabelle
        $table = parent::table($table);
        
        // Aktiviere Aktionen auch im View-Modus durch explizite Konfiguration
        return $table->recordAction(null); // Entferne Standard-Doppelklick-Aktion um Konflikte zu vermeiden
    }

    /**
     * Berechtigungsprüfung für das Bearbeiten von Dokumenten - auch im View-Modus
     */
    public function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    /**
     * Berechtigungsprüfung für das Löschen von Dokumenten - auch im View-Modus
     */
    public function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }
}
