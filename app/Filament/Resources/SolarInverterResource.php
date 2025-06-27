<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarInverterResource\Pages;
use App\Filament\Resources\SolarInverterResource\RelationManagers;
use App\Models\SolarInverter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarInverterResource extends Resource
{
    protected static ?string $model = SolarInverter::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static ?string $navigationLabel = 'Solar Wechselrichter';
    
    protected static ?string $modelLabel = 'Solar Wechselrichter';
    
    protected static ?string $pluralModelLabel = 'Solar Wechselrichter';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?string $navigationParentItem = 'Solaranlagen';

    protected static ?int $navigationSort = 2;

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
                Forms\Components\TextInput::make('rated_power_kw')
                    ->numeric(),
                Forms\Components\TextInput::make('efficiency_percent')
                    ->numeric(),
                Forms\Components\DatePicker::make('installation_date'),
                Forms\Components\TextInput::make('firmware_version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_sync_at'),
                Forms\Components\TextInput::make('input_voltage_range')
                    ->maxLength(255),
                Forms\Components\TextInput::make('output_voltage')
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_dc_current')
                    ->maxLength(255),
                Forms\Components\TextInput::make('max_ac_current')
                    ->maxLength(255),
                Forms\Components\TextInput::make('protection_class')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cooling_method')
                    ->maxLength(255),
                Forms\Components\TextInput::make('dimensions')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight_kg')
                    ->numeric(),
                Forms\Components\TextInput::make('current_power_kw')
                    ->numeric(),
                Forms\Components\TextInput::make('current_voltage_v')
                    ->numeric(),
                Forms\Components\TextInput::make('current_current_a')
                    ->numeric(),
                Forms\Components\TextInput::make('current_frequency_hz')
                    ->numeric(),
                Forms\Components\TextInput::make('current_temperature_c')
                    ->numeric(),
                Forms\Components\TextInput::make('daily_yield_kwh')
                    ->numeric(),
                Forms\Components\TextInput::make('total_yield_kwh')
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
                Tables\Columns\TextColumn::make('rated_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('efficiency_percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installation_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('firmware_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_sync_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('input_voltage_range')
                    ->searchable(),
                Tables\Columns\TextColumn::make('output_voltage')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_dc_current')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_ac_current')
                    ->searchable(),
                Tables\Columns\TextColumn::make('protection_class')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cooling_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_power_kw')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_voltage_v')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_current_a')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_frequency_hz')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_temperature_c')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_yield_kwh')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_yield_kwh')
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
            'index' => Pages\ListSolarInverters::route('/'),
            'create' => Pages\CreateSolarInverter::route('/create'),
            'edit' => Pages\EditSolarInverter::route('/{record}/edit'),
        ];
    }
}
