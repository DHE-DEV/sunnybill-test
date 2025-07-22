<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\User;
use Filament\Notifications\Notification;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Aufgaben';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->tasks()->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('priority')
                                    ->label('Priorität')
                                    ->options([
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend',
                                        'blocker' => 'Blockierend',
                                    ])
                                    ->required()
                                    ->default('medium'),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'open' => 'Offen',
                                        'in_progress' => 'In Bearbeitung',
                                        'waiting_external' => 'Warten auf extern',
                                        'waiting_internal' => 'Warten auf intern',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->required()
                                    ->default('open'),
                                Forms\Components\Select::make('task_type_id')
                                    ->label('Aufgabentyp')
                                    ->relationship('taskType', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Fälligkeitsdatum')
                                    ->native(false),
                                Forms\Components\TimePicker::make('due_time')
                                    ->label('Fälligkeitszeit')
                                    ->native(false),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_minutes')
                                    ->label('Geschätzte Minuten')
                                    ->numeric()
                                    ->minValue(0),
                                Forms\Components\TextInput::make('actual_minutes')
                                    ->label('Tatsächliche Minuten')
                                    ->numeric()
                                    ->minValue(0),
                            ]),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Zugewiesen an')
                            ->options(fn () => User::whereNotNull('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TagsInput::make('labels')
                            ->label('Labels'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->emptyStateHeading('Keine Aufgaben')
            ->emptyStateDescription('Es wurden noch keine Aufgaben zu diesem Projekt hinzugefügt.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Aufgabe erstellen'),
                Tables\Actions\AttachAction::make()
                    ->label('Existierende Aufgabe verknüpfen')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Aufgabe(n) verknüpft')
                            ->body('Die ausgewählten Aufgaben wurden erfolgreich mit dem Projekt verknüpft.')
                    ),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('task_number')
                    ->label('Aufgaben-Nr.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        'blocker' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        'blocker' => 'Blockierend',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'gray',
                        'in_progress' => 'info',
                        'waiting_external' => 'warning',
                        'waiting_internal' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warten auf extern',
                        'waiting_internal' => 'Warten auf intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeit')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(function (Task $record): string {
                        if ($record->is_overdue) {
                            return 'danger';
                        }
                        if ($record->is_due_today) {
                            return 'warning';
                        }
                        return 'gray';
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Zugewiesen an')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
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
                        'waiting_external' => 'Warten auf extern',
                        'waiting_internal' => 'Warten auf intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        'blocker' => 'Blockierend',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Zugewiesen an')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', '<', now()->toDateString())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Aufgabe erstellen'),
                Tables\Actions\AttachAction::make()
                    ->label('Existierende Aufgabe verknüpfen')
                    ->multiple()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Aufgabe(n) verknüpft')
                            ->body('Die ausgewählten Aufgaben wurden erfolgreich mit dem Projekt verknüpft.')
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\DetachAction::make()
                        ->label('Verknüpfung lösen')
                        ->icon('heroicon-o-link-slash')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Verknüpfung gelöst')
                                ->body('Die Aufgabe wurde vom Projekt getrennt.')
                        ),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Verknüpfungen lösen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function canCreate(): bool
    {
        // Erlaubt das Erstellen von Aufgaben sowohl im Edit- als auch im View-Modus
        return true;
    }

    protected function canAttach(): bool
    {
        // Erlaubt das Verknüpfen von Aufgaben sowohl im Edit- als auch im View-Modus
        return true;
    }

    protected function canDetach($record): bool
    {
        // Erlaubt das Lösen von Verknüpfungen sowohl im Edit- als auch im View-Modus
        return true;
    }

    protected function canEdit($record): bool
    {
        // Erlaubt das Bearbeiten von Aufgaben sowohl im Edit- als auch im View-Modus
        return true;
    }

    protected function canDelete($record): bool
    {
        return false; // Aufgaben sollen nur die Verknüpfung gelöst, nicht gelöscht werden
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        // Erlaubt die Anzeige des RelationManagers sowohl in View- als auch Edit-Seiten
        return true;
    }

    public function isReadOnly(): bool
    {
        // Deaktiviert den Read-Only Modus, damit Actions auch im View-Modus verfügbar sind
        return false;
    }
}
