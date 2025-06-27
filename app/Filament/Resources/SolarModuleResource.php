<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarModuleResource\Pages;
use App\Filament\Resources\SolarModuleResource\RelationManagers;
use App\Models\SolarModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SolarModuleResource extends Resource
{
    protected static ?string $model = SolarModule::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static ?string $navigationLabel = 'Solar Module';
    
    protected static ?string $modelLabel = 'Solar Modul';
    
    protected static ?string $pluralModelLabel = 'Solar Module';

    protected static ?string $navigationGroup = 'Solar Management';

    protected static ?string $navigationParentItem = 'Solaranlagen';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\TextInput::make('rated_power_wp')
                    ->numeric(),
                Forms\Components\TextInput::make('efficiency_percent')
                    ->numeric(),
                Forms\Components\DatePicker::make('installation_date'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\DateTimePicker::make('last_sync_at'),
                Forms\Components\TextInput::make('cell_type'),
                Forms\Components\TextInput::make('module_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('voltage_vmp')
                    ->numeric(),
                Forms\Components\TextInput::make('current_imp')
                    ->numeric(),
                Forms\Components\TextInput::make('voltage_voc')
                    ->numeric(),
                Forms\Components\TextInput::make('current_isc')
                    ->numeric(),
                Forms\Components\TextInput::make('temperature_coefficient')
                    ->numeric(),
                Forms\Components\TextInput::make('dimensions')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight_kg')
                    ->numeric(),
                Forms\Components\TextInput::make('frame_color')
                    ->maxLength(255),
                Forms\Components\TextInput::make('glass_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('string_number')
                    ->numeric(),
                Forms\Components\TextInput::make('position_in_string')
                    ->numeric(),
                Forms\Components\TextInput::make('orientation_degrees')
                    ->numeric(),
                Forms\Components\TextInput::make('tilt_degrees')
                    ->numeric(),
                Forms\Components\TextInput::make('shading_factor')
                    ->numeric(),
                Forms\Components\TextInput::make('current_power_w')
                    ->numeric(),
                Forms\Components\TextInput::make('current_voltage_v')
                    ->numeric(),
                Forms\Components\TextInput::make('current_current_a')
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
                Tables\Columns\TextColumn::make('rated_power_wp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('efficiency_percent')
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
                Tables\Columns\TextColumn::make('cell_type'),
                Tables\Columns\TextColumn::make('module_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('voltage_vmp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_imp')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('voltage_voc')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_isc')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('temperature_coefficient')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frame_color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('glass_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('string_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position_in_string')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orientation_degrees')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tilt_degrees')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shading_factor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_power_w')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_voltage_v')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_current_a')
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
            'index' => Pages\ListSolarModules::route('/'),
            'create' => Pages\CreateSolarModule::route('/create'),
            'edit' => Pages\EditSolarModule::route('/{record}/edit'),
        ];
    }
}
