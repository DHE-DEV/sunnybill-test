<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SupplierEmployee;

class SolarPlantsRelationManager extends RelationManager
{
    protected static string $relationship = 'solarPlants';

    protected static ?string $title = 'Zugeordnete Solaranlagen';

    protected static ?string $modelLabel = 'Solaranlage';

    protected static ?string $pluralModelLabel = 'Solaranlagen';

    protected static ?string $icon = 'heroicon-o-sun';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zuordnung')
                    ->schema([
                        Forms\Components\Select::make('supplier_employee_id')
                            ->label('Ansprechpartner')
                            ->options(function () {
                                $supplier = $this->getOwnerRecord();
                                return $supplier->employees()
                                    ->where('is_active', true)
                                    ->get()
                                    ->pluck('display_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('role')
                            ->label('Rolle/Aufgabe')
                            ->placeholder('z.B. Installateur, Wartung, Komponenten')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Beginn der Zusammenarbeit'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Ende der Zusammenarbeit'),
                    ])->columns(2),

                Forms\Components\Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Spezielle Notizen für diese Solaranlage')
                            ->rows(4)
                            ->placeholder('Besondere Vereinbarungen, Wartungsintervalle, etc.'),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktive Zuordnung')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Rolle')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplierEmployee.full_name')
                    ->label('Ansprechpartner')
                    ->getStateUsing(function ($record) {
                        $pivotData = $record->pivot;
                        if ($pivotData->supplier_employee_id) {
                            $employee = SupplierEmployee::find($pivotData->supplier_employee_id);
                            return $employee?->full_name;
                        }
                        return '-';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.start_date')
                    ->label('Beginn')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.end_date')
                    ->label('Ende')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_capacity_kw')
                    ->label('Leistung (kW)')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktive Zuordnungen')
                    ->attribute('pivot.is_active'),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rolle')
                    ->attribute('pivot.role')
                    ->options([
                        'Installateur' => 'Installateur',
                        'Wartung' => 'Wartung',
                        'Komponenten' => 'Komponenten',
                        'Planung' => 'Planung',
                        'Support' => 'Support',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('supplier_employee_id')
                            ->label('Ansprechpartner')
                            ->options(function () {
                                $supplier = $this->getOwnerRecord();
                                return $supplier->employees()
                                    ->where('is_active', true)
                                    ->get()
                                    ->pluck('display_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('role')
                            ->label('Rolle/Aufgabe')
                            ->placeholder('z.B. Installateur, Wartung, Komponenten'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Beginn'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Ende'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        Forms\Components\Select::make('supplier_employee_id')
                            ->label('Ansprechpartner')
                            ->options(function () {
                                $supplier = $this->getOwnerRecord();
                                return $supplier->employees()
                                    ->where('is_active', true)
                                    ->get()
                                    ->pluck('display_name', 'id');
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('role')
                            ->label('Rolle/Aufgabe'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Beginn'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Ende'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}