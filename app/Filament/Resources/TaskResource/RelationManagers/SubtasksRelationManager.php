<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Task;

class SubtasksRelationManager extends RelationManager
{
    protected static string $relationship = 'subtasks';

    protected static ?string $title = 'Unteraufgaben';

    protected static ?string $modelLabel = 'Unteraufgabe';

    protected static ?string $pluralModelLabel = 'Unteraufgaben';

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

                        Forms\Components\Select::make('task_type_id')
                            ->label('Aufgabentyp')
                            ->relationship('taskType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

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
                            ->options(\App\Models\User::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Nicht zugewiesen'),

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
                    ->color(fn (Task $record): string => $record->is_overdue ? 'danger' : ($record->is_due_today ? 'warning' : 'primary')),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Zugewiesen an')
                    ->searchable()
                    ->placeholder('Nicht zugewiesen'),

                Tables\Columns\TextColumn::make('estimated_minutes')
                    ->label('Geschätzt')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_minutes')
                    ->label('Tatsächlich')
                    ->suffix(' min')
                    ->sortable(),
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
            ->defaultSort('order_index');
    }
}