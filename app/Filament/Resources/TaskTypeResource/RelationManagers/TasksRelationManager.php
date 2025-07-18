<?php

namespace App\Filament\Resources\TaskTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Task;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\User;
use Filament\Forms\Get;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Aufgaben';

    protected static ?string $modelLabel = 'Aufgabe';

    protected static ?string $pluralModelLabel = 'Aufgaben';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('priority')
                            ->label('Priorität')
                            ->options([
                                'low' => 'Niedrig',
                                'medium' => 'Mittel',
                                'high' => 'Hoch',
                                'urgent' => 'Dringend',
                            ])
                            ->default('medium')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgebrochen',
                            ])
                            ->default('open')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Termine & Zeit')
                    ->schema([
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->native(false),

                        Forms\Components\TimePicker::make('due_time')
                            ->label('Fälligkeitszeit')
                            ->seconds(false),

                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('Geschätzte Minuten')
                            ->numeric()
                            ->suffix('min'),

                        Forms\Components\TextInput::make('actual_minutes')
                            ->label('Tatsächliche Minuten')
                            ->numeric()
                            ->suffix('min'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zuordnungen')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Zugewiesen an')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'company_name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('supplier_id')
                            ->label('Lieferant')
                            ->relationship('supplier', 'company_name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(function () {
                                $options = ['all' => 'Alle Solaranlagen'];
                                $solarPlants = SolarPlant::whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name')
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();
                                return $options + $solarPlants;
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'all') {
                                    $set('applies_to_all_solar_plants', true);
                                    $set('solar_plant_id', null);
                                } else {
                                    $set('applies_to_all_solar_plants', false);
                                }
                            })
                            ->helperText(fn (Forms\Get $get): string => 
                                $get('applies_to_all_solar_plants') 
                                    ? '🌟 Diese Aufgabe gilt für alle Solaranlagen. Beim Abschließen wird für jede Solaranlage eine abgeschlossene Aufgabe erstellt.'
                                    : 'Wählen Sie eine spezifische Solaranlage oder "Alle Solaranlagen" aus.'
                            ),

                        Forms\Components\Hidden::make('applies_to_all_solar_plants')
                            ->default(false),

                        Forms\Components\Select::make('parent_task_id')
                            ->label('Übergeordnete Aufgabe')
                            ->relationship('parentTask', 'title')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Erweiterte Optionen')
                    ->schema([
                        Forms\Components\TagsInput::make('labels')
                            ->label('Labels')
                            ->placeholder('Label hinzufügen...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Wiederkehrend'),

                        Forms\Components\TextInput::make('recurring_pattern')
                            ->label('Wiederholungsmuster')
                            ->placeholder('z.B. daily, weekly, monthly')
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring')),

                        Forms\Components\TextInput::make('order_index')
                            ->label('Sortierreihenfolge')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'open',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fällig am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Task $record): string => $record->is_overdue ? 'danger' : 'primary'),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Zugewiesen an')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Kunde')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estimated_minutes')
                    ->label('Geschätzt')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actual_minutes')
                    ->label('Tatsächlich')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ]),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => $query->overdue()),

                Tables\Filters\Filter::make('due_today')
                    ->label('Heute fällig')
                    ->query(fn (Builder $query): Builder => $query->dueToday()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Abschließen')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Task $record) => $record->markAsCompleted())
                    ->visible(fn (Task $record): bool => $record->status !== 'completed'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
