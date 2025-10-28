<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantMonthlyOverviewResource\Pages;
use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SolarPlantMonthlyOverviewResource extends Resource
{
    protected static ?string $model = SolarPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Monatliche Detailansicht';

    protected static ?string $modelLabel = 'Monatliche Detailansicht';

    protected static ?string $pluralModelLabel = 'Monatliche Detailansicht';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kein Form benötigt - nur Übersicht
            ]);
    }

    public static function table(Table $table): Table
    {
        // Diese Resource wird primär über eine Custom Page verwendet
        return $table
            ->columns([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlantMonthlyOverview::route('/'),
        ];
    }

    /**
     * Ermittelt den Abrechnungsstatus für einen bestimmten Monat
     */
    public static function getBillingStatusForMonth(SolarPlant $solarPlant, string $month): string
    {
        $activeContracts = $solarPlant->activeSupplierContracts()->with('supplier')->get();

        // Filtere gelöschte Verträge und entferne Duplikate
        $uniqueContracts = $activeContracts->filter(function ($contract) {
            return $contract->deleted_at === null;
        })->unique('id');

        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        // Erstelle Carbon-Datum für den Monat (erster Tag des Monats)
        $monthDate = Carbon::create($year, $monthNumber, 1);

        // Filtere Verträge, die für diesen Monat gültig sind
        $validContracts = $uniqueContracts->filter(function ($contract) use ($monthDate) {
            // Prüfe ob Vertrag aktiv ist
            if (!$contract->is_active) {
                return false;
            }

            // Wenn start_date gesetzt ist, prüfe ob der Monat danach liegt
            if ($contract->start_date && $monthDate->isBefore($contract->start_date->startOfMonth())) {
                return false;
            }

            // Wenn end_date gesetzt ist, prüfe ob der Monat davor liegt
            if ($contract->end_date && $monthDate->isAfter($contract->end_date->endOfMonth())) {
                return false;
            }

            return true;
        });

        if ($validContracts->isEmpty()) {
            return 'Keine Verträge';
        }

        $contractsWithBilling = $validContracts->filter(function ($contract) use ($year, $monthNumber) {
            return $contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $monthNumber)
                ->exists();
        });

        return $contractsWithBilling->count() === $validContracts->count() ? 'Vollständig' : 'Unvollständig';
    }

    /**
     * Prüft ob für einen bestimmten Monat Anlagen-Abrechnungen (SolarPlantBilling) existieren
     */
    public static function hasPlantBillingsForMonth(SolarPlant $solarPlant, string $month): bool
    {
        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        return $solarPlant->billings()
            ->where('billing_year', $year)
            ->where('billing_month', $monthNumber)
            ->exists();
    }

    /**
     * Ermittelt die Anzahl der Anlagen-Abrechnungen für einen bestimmten Monat
     */
    public static function getPlantBillingsCountForMonth(SolarPlant $solarPlant, string $month): int
    {
        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        return $solarPlant->billings()
            ->where('billing_year', $year)
            ->where('billing_month', $monthNumber)
            ->count();
    }

    /**
     * Ermittelt fehlende Abrechnungen für einen bestimmten Monat
     */
    public static function getMissingBillingsForMonth(SolarPlant $solarPlant, string $month): Collection
    {
        $activeContracts = $solarPlant->activeSupplierContracts()->with('supplier')->get();
        $year = (int) substr($month, 0, 4);
        $monthNumber = (int) substr($month, 5, 2);

        // Erstelle Carbon-Datum für den Monat (erster Tag des Monats)
        $monthDate = Carbon::create($year, $monthNumber, 1);

        // Filtere gelöschte Verträge und entferne Duplikate
        $uniqueContracts = $activeContracts->filter(function ($contract) {
            return $contract->deleted_at === null;
        })->unique('id');

        // Filtere Verträge, die für diesen Monat gültig sind
        $validContracts = $uniqueContracts->filter(function ($contract) use ($monthDate) {
            // Prüfe ob Vertrag aktiv ist
            if (!$contract->is_active) {
                return false;
            }

            // Wenn start_date gesetzt ist, prüfe ob der Monat danach liegt
            if ($contract->start_date && $monthDate->isBefore($contract->start_date->startOfMonth())) {
                return false;
            }

            // Wenn end_date gesetzt ist, prüfe ob der Monat davor liegt
            if ($contract->end_date && $monthDate->isAfter($contract->end_date->endOfMonth())) {
                return false;
            }

            return true;
        });

        return $validContracts->filter(function ($contract) use ($year, $monthNumber) {
            return !$contract->billings()
                ->where('billing_year', $year)
                ->where('billing_month', $monthNumber)
                ->exists();
        });
    }

    /**
     * Generiert eine Liste aller verfügbaren Monate von Januar 2025 bis heute
     */
    public static function getAvailableMonths(): array
    {
        $months = [];
        $startDate = Carbon::create(2025, 1, 1);
        $currentDate = now();
        
        $date = $startDate->copy();
        while ($date->lessThanOrEqualTo($currentDate)) {
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('de')->translatedFormat('F Y'),
            ];
            $date->addMonth();
        }
        
        // Neueste Monate zuerst
        return array_reverse($months);
    }
}
