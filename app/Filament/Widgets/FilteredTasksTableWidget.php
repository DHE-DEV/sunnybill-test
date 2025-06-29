<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FilteredTasksTableWidget extends BaseWidget
{
    public ?string $timeFilter = 'today';
    public ?string $userFilter = 'all';
    
    protected static ?string $heading = 'Aufgaben';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';

    protected $listeners = ['timeFilterChanged', 'userFilterChanged'];

    public function mount(?string $timeFilter = null, ?string $userFilter = null): void
    {
        $this->timeFilter = $timeFilter ?? 'today';
        $this->userFilter = $userFilter ?? 'all';
    }

    public function booted(): void
    {
        // Stelle sicher, dass der Filter beim ersten Laden angewendet wird
        if (!$this->timeFilter) {
            $this->timeFilter = 'today';
        }
        if (!$this->userFilter) {
            $this->userFilter = 'all';
        }
    }

    public function timeFilterChanged($timeFilter): void
    {
        $this->timeFilter = $timeFilter;
        // Trigger table refresh
        $this->resetTable();
        // Force re-render
        $this->dispatch('$refresh');
    }

    public function userFilterChanged($userFilter): void
    {
        $this->userFilter = $userFilter;
        // Trigger table refresh
        $this->resetTable();
        // Force re-render
        $this->dispatch('$refresh');
    }

    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        
        return $table
            ->query(
                Task::query()
                    ->whereBetween('due_date', $dateRange)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->when($this->userFilter !== 'all', function (Builder $query) {
                        if ($this->userFilter === 'owner') {
                            $query->whereNotNull('owner_id');
                        } elseif ($this->userFilter === 'assigned') {
                            $query->whereNotNull('assigned_user_id');
                        } elseif ($this->userFilter === 'no_owner') {
                            $query->whereNull('owner_id');
                        } elseif ($this->userFilter === 'no_assigned') {
                            $query->whereNull('assigned_user_id');
                        } elseif (is_numeric($this->userFilter)) {
                            $query->where(function (Builder $q) {
                                $q->where('owner_id', $this->userFilter)
                                  ->orWhere('assigned_user_id', $this->userFilter);
                            });
                        }
                    })
                    ->with(['taskType', 'assignedUser', 'owner', 'customer'])
                    ->orderByRaw("
                        CASE
                            WHEN priority = 'urgent' THEN 1
                            WHEN priority = 'high' THEN 2
                            WHEN due_date < CURDATE() THEN 3
                            WHEN due_date = CURDATE() THEN 4
                            ELSE 5
                        END
                    ")
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                TextColumn::make('task_number')
                    ->label('Nr.')
                    ->searchable()
                    ->sortable()
                    ->width('100px')
                    ->weight('bold')
                    ->toggleable(),
                    
                TextColumn::make('taskType.name')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (Task $record): string => $record->taskType->color ?? 'primary')
                    ->width('120px')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(30)
                    ->weight('bold')
                    ->tooltip(fn (Task $record): string => $record->title)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('due_date')
                    ->label('Fällig')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Task $record): string =>
                        $record->is_overdue ? 'danger' :
                        ($record->is_due_today ? 'warning' : 'primary')
                    )
                    ->icon(fn (Task $record): string =>
                        $record->is_overdue ? 'heroicon-o-exclamation-triangle' :
                        ($record->is_due_today ? 'heroicon-o-clock' : '')
                    )
                    ->width('100px')
                    ->toggleable(isToggledHiddenByDefault: false),

                BadgeColumn::make('priority')
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
                    })
                    ->sortable()
                    ->width('100px')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'open',
                        'primary' => 'in_progress',
                        'warning' => 'waiting_external',
                        'info' => 'waiting_internal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warte Extern',
                        'waiting_internal' => 'Warte Intern',
                        default => $state,
                    })
                    ->sortable()
                    ->width('120px')
                    ->toggleable(),

                TextColumn::make('owner.name')
                    ->label('Inhaber')
                    ->limit(15)
                    ->placeholder('Kein Inhaber')
                    ->tooltip(fn (Task $record): ?string => $record->owner?->name)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('assignedUser.name')
                    ->label('Zuständig')
                    ->limit(15)
                    ->placeholder('Nicht zuständig')
                    ->tooltip(fn (Task $record): ?string => $record->assignedUser?->name)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('customer.company_name')
                    ->label('Kunde')
                    ->limit(15)
                    ->placeholder('Kein Kunde')
                    ->tooltip(fn (Task $record): ?string => $record->customer?->company_name)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Task $record): string => route('filament.admin.resources.tasks.view', $record))
                        ->openUrlInNewTab(false),
                        
                    Tables\Actions\Action::make('edit')
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Task $record): string => route('filament.admin.resources.tasks.edit', $record))
                        ->openUrlInNewTab(false),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_filter')
                    ->label('Benutzer-Filter')
                    ->options([
                        'all' => 'Alle',
                        'owner' => 'Mit Inhaber',
                        'assigned' => 'Mit Zuständigem',
                        'no_owner' => 'Ohne Inhaber',
                        'no_assigned' => 'Ohne Zuständigen',
                        ...User::all()->pluck('name', 'id')->toArray()
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? 'all';
                        
                        if ($value === 'all') {
                            return $query;
                        } elseif ($value === 'owner') {
                            return $query->whereNotNull('owner_id');
                        } elseif ($value === 'assigned') {
                            return $query->whereNotNull('assigned_user_id');
                        } elseif ($value === 'no_owner') {
                            return $query->whereNull('owner_id');
                        } elseif ($value === 'no_assigned') {
                            return $query->whereNull('assigned_user_id');
                        } elseif (is_numeric($value)) {
                            return $query->where(function (Builder $q) use ($value) {
                                $q->where('owner_id', $value)
                                  ->orWhere('assigned_user_id', $value);
                            });
                        }
                        
                        return $query;
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('toggleColumns')
                    ->label('Spalten')
                    ->icon('heroicon-o-view-columns')
                    ->color('gray')
                    ->button()
                    ->action(fn () => null)
                    ->extraAttributes(['x-data' => '{}', 'x-on:click' => '$dispatch("toggle-table-columns")']),
            ])
            ->emptyStateHeading('Keine Aufgaben gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum gibt es keine offenen Aufgaben.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->heading('Aufgaben (' . $this->getPeriodLabel() . ')');
    }
    
    protected function getDateRange(): array
    {
        // Startdatum ist immer weit in der Vergangenheit, um überfällige Aufgaben einzuschließen
        $startDate = Carbon::today()->subYears(10)->toDateString();
        
        return match($this->timeFilter) {
            'today' => [
                $startDate,
                Carbon::today()->toDateString()
            ],
            'next_7_days' => [
                $startDate,
                Carbon::today()->copy()->addDays(7)->toDateString()
            ],
            'next_30_days' => [
                $startDate,
                Carbon::today()->copy()->addDays(30)->toDateString()
            ],
            default => [
                $startDate,
                Carbon::today()->toDateString()
            ]
        };
    }
    
    protected function getPeriodLabel(): string
    {
        return match($this->timeFilter) {
            'today' => 'Heute',
            'next_7_days' => 'Nächste 7 Tage',
            'next_30_days' => 'Nächste 30 Tage',
            default => 'Heute'
        };
    }
}