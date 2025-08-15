<?php

namespace App\Filament\Exports;

use App\Models\SolarPlant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SolarPlantExporter extends Exporter
{
    protected static ?string $model = SolarPlant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Anlagenname'),
            ExportColumn::make('app_code')
                ->label('App-Code'),
            ExportColumn::make('location')
                ->label('Standort'),
            ExportColumn::make('plot_number')
                ->label('Flurstück'),
            ExportColumn::make('total_capacity_kw')
                ->label('Gesamtleistung (kWp)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') : ''),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(function ($state) {
                    $status = \App\Models\SolarPlantStatus::where('key', $state)->first();
                    return $status ? $status->name : $state;
                }),
            ExportColumn::make('is_active')
                ->label('Aktiv')
                ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein'),
            ExportColumn::make('total_participation')
                ->label('Beteiligung (%)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') : '0,00'),
            ExportColumn::make('participations_count')
                ->label('Anzahl Kunden')
                ->counts('participations'),
            ExportColumn::make('planned_installation_date')
                ->label('Geplante Installation')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y') : ''),
            ExportColumn::make('installation_date')
                ->label('Tatsächliche Installation')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y') : ''),
            ExportColumn::make('planned_commissioning_date')
                ->label('Geplante Inbetriebnahme')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y') : ''),
            ExportColumn::make('commissioning_date')
                ->label('Tatsächliche Inbetriebnahme')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y') : ''),
            ExportColumn::make('mastr_number_unit')
                ->label('MaStR-Nr. der Einheit'),
            ExportColumn::make('mastr_number_eeg_plant')
                ->label('MaStR-Nr. der EEG-Anlage'),
            ExportColumn::make('malo_id')
                ->label('MaLo-ID'),
            ExportColumn::make('melo_id')
                ->label('MeLo-ID'),
            ExportColumn::make('vnb_process_number')
                ->label('VNB-Vorgangsnummer'),
            ExportColumn::make('pv_soll_project_number')
                ->label('PV-Soll Projektnummer'),
            ExportColumn::make('panel_count')
                ->label('Anzahl Module'),
            ExportColumn::make('inverter_count')
                ->label('Anzahl Wechselrichter'),
            ExportColumn::make('battery_capacity_kwh')
                ->label('Batteriekapazität (kWh)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') : ''),
            ExportColumn::make('total_investment')
                ->label('Gesamtinvestition (€)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') : ''),
            ExportColumn::make('annual_operating_costs')
                ->label('Jährliche Betriebskosten (€)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, ',', '.') : ''),
            ExportColumn::make('feed_in_tariff_per_kwh')
                ->label('Einspeisevergütung (€/kWh)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') : ''),
            ExportColumn::make('electricity_price_per_kwh')
                ->label('Strompreis (€/kWh)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') : ''),
            ExportColumn::make('expected_annual_yield_kwh')
                ->label('Erwarteter Jahresertrag (kWh)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') : ''),
            ExportColumn::make('latitude')
                ->label('Breitengrad'),
            ExportColumn::make('longitude')
                ->label('Längengrad'),
            ExportColumn::make('description')
                ->label('Beschreibung'),
            ExportColumn::make('notes')
                ->label('Notizen'),
            ExportColumn::make('created_at')
                ->label('Erstellt am')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : ''),
            ExportColumn::make('updated_at')
                ->label('Zuletzt geändert')
                ->formatStateUsing(fn ($state) => $state ? $state->format('d.m.Y H:i') : ''),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Der Solaranlagen-Export wurde abgeschlossen und ' . number_format($export->successful_rows) . ' ' . str('Datensatz')->plural($export->successful_rows) . ' exportiert.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('Datensatz')->plural($failedRowsCount) . ' konnten nicht exportiert werden.';
        }

        return $body;
    }
}
