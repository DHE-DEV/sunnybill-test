<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SolarPlantBilling;

class SolarPlantBillingsRelationManager extends RelationManager
{
    protected static string $relationship = 'solarPlantBillings';

    protected static ?string $title = 'Abrechnungen';

    protected static ?string $modelLabel = 'Abrechnung';

    protected static ?string $pluralModelLabel = 'Abrechnungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form is read-only, managed via SolarPlantBillingResource
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->searchable()
                    ->sortable()
                    ->url(fn (SolarPlantBilling $record): string =>
                        \App\Filament\Resources\SolarPlantResource::getUrl('view', ['record' => $record->solar_plant_id])
                    )
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung (%)')
                    ->suffix('%')
                    ->numeric(2)
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_total_costs')
                    ->label('Kosten')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_total_credits')
                    ->label('Gutschriften')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('formatted_net_amount')
                    ->label('Gesamtbetrag')
                    ->alignRight()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'finalized' => 'warning',
                        'sent' => 'info',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('cancellation_date')
                    ->label('Storniert am')
                    ->date()
                    ->sortable()
                    ->color('danger')
                    ->badge(fn ($record) => $record->cancellation_date ? true : false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_year')
                    ->label('Jahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions()),
            ])
            ->headerActions([
                // No create action - billings are created via SolarPlantBillingResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (SolarPlantBilling $record): string =>
                        \App\Filament\Resources\SolarPlantBillingResource::getUrl('view', ['record' => $record->id])
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('billing_year', 'desc')
            ->defaultSort('billing_month', 'desc');
    }
}
