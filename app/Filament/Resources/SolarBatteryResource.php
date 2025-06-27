<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarBatteryResource\Pages;
use App\Filament\Resources\SolarBatteryResource\RelationManagers;
use App\Models\SolarBattery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarBatteryResource extends Resource
{
    protected static ?string $model = SolarBattery::class;

    protected static ?string $navigationIcon = 'heroicon-o-battery-100';
    
    protected static ?string $navigationLabel = 'Solar Batterien';
    
    protected static ?string $modelLabel = 'Solar Batterie';
    
    protected static ?string $pluralModelLabel = 'Solar Batterien';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?string $navigationParentItem = 'Solaranlagen';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('solar_plant_id')
                    ->relationship('solarPlant', 'name')
                    ->required(),
                Forms\Components\TextInput::make('fusion_solar_device_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255),
                Forms\Components\TextInput::make('serial_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('manufacturer')
                    ->maxLength(255),
                Forms\Components\TextInput::make('capacity_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('usable_capacity_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('rated_power_kw')
                    ->numeric(),
                Forms\Components\DatePicker::make('installation_date'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_sync_at'),
                Forms\Components\TextInput::make('battery_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('chemistry'),
                Forms\Components\TextInput::make('nominal_voltage_v')
                    ->numeric(),
                Forms\Components\TextInput::make('max_charge_power_kw')
                    ->numeric(),
                Forms\Components\TextInput::make('max_discharge_power_kw')
                    ->numeric(),
                Forms\Components\TextInput::make('efficiency_percent')
                    ->numeric(),
                Forms\Components\TextInput::make('cycle_life')
                    ->numeric(),
                Forms\Components\TextInput::make('warranty_years')
                    ->numeric(),
                Forms\Components\TextInput::make('operating_temp_min')
                    ->numeric(),
                Forms\Components\TextInput::make('operating_temp_max')
                    ->numeric(),
                Forms\Components\TextInput::make('dimensions')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight_kg')
                    ->numeric(),
                Forms\Components\TextInput::make('protection_class')
                    ->maxLength(255),
                Forms\Components\TextInput::make('current_soc_percent')
                    ->numeric(),
                Forms\Components\TextInput::make('current_voltage_v')
                    ->numeric(),
                Forms\Components\TextInput::make('current_current_a')
                    ->numeric(),
                Forms\Components\TextInput::make('current_power_kw')
                    ->numeric(),
                Forms\Components\TextInput::make('current_temperature_c')
                    ->numeric(),
                Forms\Components\TextInput::make('charge_cycles')
                    ->numeric(),
                Forms\Components\TextInput::make('daily_charge_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('daily_discharge_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('total_charge_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('total_discharge_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('health_percent')
                    ->numeric(),
                Forms\Components\TextInput::make('remaining_capacity_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('degradation_percent')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fusion_solar_device_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usable_capacity_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rated_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installation_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('battery_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('chemistry'),
                Tables\Columns\TextColumn::make('nominal_voltage_v')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_charge_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_discharge_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('efficiency_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cycle_life')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warranty_years')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operating_temp_min')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operating_temp_max')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('protection_class')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_soc_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_voltage_v')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_current_a')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_temperature_c')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charge_cycles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_charge_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_discharge_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_charge_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_discharge_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('health_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_capacity_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('degradation_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSolarBatteries::route('/'),
            'create' => Pages\CreateSolarBattery::route('/create'),
            'edit' => Pages\EditSolarBattery::route('/{record}/edit'),
        ];
    }
}
