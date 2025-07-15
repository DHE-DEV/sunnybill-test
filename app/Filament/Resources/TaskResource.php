<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Aufgabe';

    protected static ?string $pluralModelLabel = 'Aufgaben';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        TextInput::make('task_number')
                            ->label('Aufgabennummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                            
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Select::make('task_type_id')
                            ->label('Aufgabentyp')
                            ->relationship('taskType', 'name', function ($query) {
                                return $query->active()
                                    ->ordered()
                                    ->whereNotNull('name')
                                    ->where('name', '!=', '');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Aufgabentyp auswählen...'),

                        Select::make('priority')
                            ->label('Priorität')
                            ->options([
                                'low' => 'Niedrig',
                                'medium' => 'Mittel',
                                'high' => 'Hoch',
                                'urgent' => 'Dringend',
                            ])
                            ->default('medium')
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'waiting_external' => 'Warte auf Extern',
                                'waiting_internal' => 'Warte auf Intern',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgebrochen',
                            ])
                            ->default('open')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Termine & Zeit')
                    ->schema([
                        DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->native(false),

                        TimePicker::make('due_time')
                            ->label('Fälligkeitszeit')
                            ->seconds(false),

                        TextInput::make('estimated_minutes')
                            ->label('Geschätzte Minuten')
                            ->numeric()
                            ->suffix('min'),

                        TextInput::make('actual_minutes')
                            ->label('Tatsächliche Minuten')
                            ->numeric()
                            ->suffix('min'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zuordnungen')
                    ->schema([
                        Select::make('assigned_to')
                            ->label('Zugewiesen an')
                            ->relationship('assignedUser', 'name', function ($query) {
                                return $query->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Benutzer auswählen...')
                            ->default(auth()->id())
                            ->nullable(),

                        Select::make('owner_id')
                            ->label('Inhaber')
                            ->relationship('owner', 'name', function ($query) {
                                return $query->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Inhaber auswählen...')
                            ->default(auth()->id())
                            ->nullable(),

                        Select::make('customer_id')
                            ->label('Kunde')
                            ->relationship('customer', 'company_name', function ($query) {
                                return $query->whereNotNull('company_name')
                                    ->where('company_name', '!=', '')
                                    ->orderBy('company_name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Kunde auswählen...')
                            ->nullable(),

                        Select::make('supplier_id')
                            ->label('Lieferant')
                            ->relationship('supplier', 'company_name', function ($query) {
                                return $query->whereNotNull('company_name')
                                    ->where('company_name', '!=', '')
                                    ->orderBy('company_name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Lieferant auswählen...')
                            ->nullable(),

                        Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->relationship('solarPlant', 'name', function ($query) {
                                return $query->whereNotNull('name')
                                    ->where('name', '!=', '')
                                    ->orderBy('name');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Solaranlage auswählen...')
                            ->nullable(),

                        Select::make('parent_task_id')
                            ->label('Übergeordnete Aufgabe')
                            ->relationship('parentTask', 'title', function ($query) {
                                return $query->whereNotNull('title')
                                    ->where('title', '!=', '')
                                    ->orderBy('title');
                            })
                            ->getOptionLabelFromRecordUsing(fn (Task $record): string =>
                                ($record->taskType && $record->taskType->name ? "[{$record->taskType->name}] " : "") . $record->title
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Übergeordnete Aufgabe auswählen...')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Erweiterte Optionen')
                    ->schema([
                        TagsInput::make('labels')
                            ->label('Labels')
                            ->placeholder('Label hinzufügen...')
                            ->columnSpanFull(),

                        Toggle::make('is_recurring')
                            ->label('Wiederkehrend'),

                        TextInput::make('recurring_pattern')
                            ->label('Wiederholungsmuster')
                            ->placeholder('z.B. daily, weekly, monthly')
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring')),

                        TextInput::make('order_index')
                            ->label('Sortierreihenfolge')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(function (Task $record): string {
                        $title = '<span style="color: #f59e0b; font-weight: bold;">' . e($record->title) . '</span>';
                        $description = $record->description;
                        
                        if ($description && strlen($description) > 0) {
                            $shortDescription = strlen($description) > 40
                                ? substr($description, 0, 40) . '...'
                                : $description;
                            return $title . '<br><span style="color: #6b7280; font-size: 0.875rem; font-weight: normal;">' . e($shortDescription) . '</span>';
                        }
                        
                        return $title;
                    })
                    ->html()
                    ->wrap(),

                TextColumn::make('task_number')
                    ->label('Nr.')
                    ->searchable()
                    ->sortable()
                    ->width('120px')
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('parentTask.task_number')
                    ->label('Gehört zu')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Hauptaufgabe')
                    ->width('120px')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(fn (Task $record): string => $record->parentTask ? route('filament.admin.resources.tasks.view', $record->parentTask) : '')
                    ->color('primary')
                    ->weight('medium'),
                    
                BadgeColumn::make('taskType.name')
                    ->label('Typ')
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->colors([
                        'gray' => 'low',
                        'blue' => 'medium',
                        'orange' => 'high',
                        'red' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'open',
                        'blue' => 'in_progress',
                        'yellow' => 'waiting_external',
                        'purple' => 'waiting_internal',
                        'green' => 'completed',
                        'red' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warte auf Extern',
                        'waiting_internal' => 'Warte auf Intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('due_date')
                    ->label('Fällig am')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Task $record): string => $record->is_overdue ? 'danger' : ($record->is_due_today ? 'warning' : 'primary'))
                    ->icon(fn (Task $record): string => $record->is_overdue ? 'heroicon-o-exclamation-triangle' : ($record->is_due_today ? 'heroicon-o-clock' : '')),

                TextColumn::make('owner.name')
                    ->label('Inhaber')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Kein Inhaber')
                    ->toggleable(),

                TextColumn::make('assignedUser.name')
                    ->label('Zuständig')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Nicht zuständig')
                    ->toggleable(),

                TextColumn::make('customer.company_name')
                    ->label('Kunde')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estimated_minutes')
                    ->label('Geschätzt')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('actual_minutes')
                    ->label('Tatsächlich')
                    ->suffix(' min')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subtasks_count')
                    ->label('Unteraufgaben')
                    ->counts('subtasks')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('completed_at')
                    ->label('Abgeschlossen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('task_type_id')
                    ->label('Aufgabentyp')
                    ->relationship('taskType', 'name', function ($query) {
                        return $query->active()
                            ->ordered()
                            ->whereNotNull('name')
                            ->where('name', '!=', '');
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warte auf Extern',
                        'waiting_internal' => 'Warte auf Intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),

                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('Zugewiesen an')
                    ->relationship('assignedUser', 'name', function ($query) {
                        return $query->whereNotNull('name')
                            ->where('name', '!=', '')
                            ->orderBy('name');
                    })
                    ->searchable()
                    ->preload(),


                Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->toggle(),

                Filter::make('due_today')
                    ->label('Heute fällig')
                    ->query(fn (Builder $query): Builder => $query->dueToday())
                    ->toggle(),

                Filter::make('high_priority')
                    ->label('Hohe Priorität')
                    ->query(fn (Builder $query): Builder => $query->highPriority())
                    ->toggle(),

                Filter::make('main_tasks')
                    ->label('Hauptaufgaben')
                    ->query(fn (Builder $query): Builder => $query->mainTasks())
                    ->toggle(),

                Filter::make('my_tasks')
                    ->label('Nur meine Aufgaben')
                    ->query(fn (Builder $query): Builder => $query->where(function ($q) {
                        $q->where('assigned_to', auth()->id())
                          ->orWhere('owner_id', auth()->id())
                          ->orWhere('created_by', auth()->id());
                    }))
                    ->toggle(),

                Filter::make('owned_by_me')
                    ->label('Ich bin Inhaber')
                    ->query(fn (Builder $query): Builder => $query->where('owner_id', auth()->id()))
                    ->toggle(),

                Filter::make('assigned_to_me')
                    ->label('Mir zugewiesen')
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', auth()->id()))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function (Task $record) {
                            $duplicatedTask = $record->duplicate();
                            return redirect()->route('filament.admin.resources.tasks.edit', $duplicatedTask);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Aufgabe duplizieren')
                        ->modalDescription('Möchten Sie diese Aufgabe wirklich duplizieren? Alle Unteraufgaben werden ebenfalls kopiert.')
                        ->modalSubmitActionLabel('Duplizieren'),
                    Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Task $record) => $record->markAsCompleted())
                        ->visible(fn (Task $record): bool => $record->status !== 'completed'),
                    Action::make('start')
                        ->label('Starten')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->action(fn (Task $record) => $record->markAsInProgress())
                        ->visible(fn (Task $record): bool => $record->status === 'open'),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
                    ->label('Aktionen')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->duplicate();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Aufgaben duplizieren')
                        ->modalDescription('Möchten Sie die ausgewählten Aufgaben wirklich duplizieren? Alle Unteraufgaben werden ebenfalls kopiert.')
                        ->modalSubmitActionLabel('Duplizieren')
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubtasksRelationManager::class,
            RelationManagers\NotesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotIn('status', ['completed', 'cancelled'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::overdue()->count();
        return $count > 0 ? 'danger' : 'primary';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['task_number', 'title', 'description', 'taskType.name', 'customer.company_name', 'assignedUser.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Typ' => $record->taskType->name,
            'Status' => match($record->status) {
                'open' => 'Offen',
                'in_progress' => 'In Bearbeitung',
                'waiting_external' => 'Warte auf Extern',
                'waiting_internal' => 'Warte auf Intern',
                'completed' => 'Abgeschlossen',
                'cancelled' => 'Abgebrochen',
                default => $record->status,
            },
            'Zugewiesen an' => $record->assignedUser?->name ?? 'Nicht zugewiesen',
        ];
    }
}
