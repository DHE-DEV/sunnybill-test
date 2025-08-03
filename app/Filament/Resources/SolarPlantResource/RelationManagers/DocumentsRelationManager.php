<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use App\Traits\HasPersistentTableState;
use App\Services\DocumentUploadConfig;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait, HasPersistentTableState;

    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Dokumente';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->documents()->count();
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
     * Überschreibe Berechtigungen für View-Modus
     */
    public function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public function canView($record): bool
    {
        return true; // Alle können Dokumente anzeigen
    }

    /**
     * Aktiviere Aktionen auch im View-Modus
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    /**
     * Überschreibe isReadOnly um Aktionen im View-Modus zu erlauben
     */
    public function isReadOnly(): bool
    {
        return false; // Erlaube Aktionen auch im View-Modus
    }

    /**
     * Überschreibe canDeleteAny für Bulk-Aktionen
     */
    public function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    /**
     * Überschreibe canEditAny für Bulk-Aktionen
     */
    public function canEditAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }
}
