<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantBillingResource\Pages;
use App\Models\SolarPlantBilling;
use App\Models\SolarPlant;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class SolarPlantBillingResource extends Resource
{
    protected static ?string $model = SolarPlantBilling::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Abrechnung Solaranlagen';

    protected static ?string $modelLabel = 'Solaranlagen-Abrechnung';

    protected static ?string $pluralModelLabel = 'Solaranlagen-Abrechnungen';

    protected static ?string $navigationGroup = 'Fakturierung';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abrechnungsdaten')
                    ->schema([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plant = SolarPlant::find($state);
                                    if ($plant) {
                                        $set('plant_info', [
                                            'plant_number' => $plant->plant_number,
                                            'location' => $plant->location,
                                        ]);
                                    }
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('plant_info')
                            ->label('Anlagen-Info')
                            ->content(function ($get) {
                                $plantId = $get('solar_plant_id');
                                if (!$plantId) return 'Keine Anlage ausgewählt';
                                
                                $plant = SolarPlant::find($plantId);
                                if (!$plant) return 'Anlage nicht gefunden';
                                
                                return "Nummer: {$plant->plant_number}\nStandort: {$plant->location}";
                            })
                            ->visible(fn ($get) => $get('solar_plant_id')),

                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->options(Customer::all()->mapWithKeys(function ($customer) {
                                $displayName = $customer->customer_type === 'business' && $customer->company_name 
                                    ? $customer->company_name 
                                    : $customer->name;
                                return [$customer->id => $displayName];
                            }))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options([
                                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                            ])
                            ->default(now()->month)
                            ->required(),

                        Forms\Components\TextInput::make('participation_percentage')
                            ->label('Beteiligungsprozentsatz')
                            ->suffix('%')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Beträge')
                    ->schema([
                        Forms\Components\TextInput::make('total_costs')
                            ->label('Gesamtkosten')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\TextInput::make('total_credits')
                            ->label('Gesamtgutschriften')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('Nettobetrag')
                            ->prefix('€')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SolarPlantBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.plant_number')
                    ->label('Anlagen-Nr.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Anlagenname')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->getStateUsing(function (SolarPlantBilling $record): string {
                        $customer = $record->customer;
                        return $customer->customer_type === 'business' && $customer->company_name 
                            ? $customer->company_name 
                            : $customer->name;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_month')
                    ->label('Abrechnungsmonat')
                    ->sortable(['billing_year', 'billing_month']),

                Tables\Columns\TextColumn::make('participation_percentage')
                    ->label('Beteiligung')
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
                    ->label('Nettobetrag')
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
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => SolarPlantBilling::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SolarPlantBilling::getStatusOptions()),

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

                Tables\Filters\SelectFilter::make('billing_month')
                    ->label('Monat')
                    ->options([
                        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_monthly_billings')
                    ->label('Monatliche Abrechnungen erstellen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::orderBy('plant_number')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 1; $i <= $currentYear; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options([
                                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
                            ])
                            ->default(now()->subMonth()->month)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $billings = SolarPlantBilling::createBillingsForMonth(
                                $data['solar_plant_id'],
                                $data['billing_year'],
                                $data['billing_month']
                            );

                            $count = count($billings);
                            $monthName = Carbon::createFromDate($data['billing_year'], $data['billing_month'], 1)
                                ->locale('de')
                                ->translatedFormat('F Y');

                            Notification::make()
                                ->title('Abrechnungen erfolgreich erstellt')
                                ->body("{$count} Abrechnungen für {$monthName} wurden erstellt.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler beim Erstellen der Abrechnungen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('md'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlantBillings::route('/'),
            'create' => Pages\CreateSolarPlantBilling::route('/create'),
            'view' => Pages\ViewSolarPlantBilling::route('/{record}'),
            'edit' => Pages\EditSolarPlantBilling::route('/{record}/edit'),
        ];
    }
}