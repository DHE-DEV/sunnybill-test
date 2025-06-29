<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class TasksOverviewTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Wichtige Aufgaben';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where(function (Builder $query) {
                        $query->where('due_date', '<=', now()->addDays(7)) // Fällig in den nächsten 7 Tagen
                            ->orWhere('priority', 'urgent') // Oder dringend
                            ->orWhere('status', 'in_progress'); // Oder in Bearbeitung
                    })
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->with(['taskType', 'assignedUser', 'customer'])
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
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('task_number')
                    ->label('Nr.')
                    ->searchable()
                    ->sortable()
                    ->width('100px')
                    ->weight('bold'),
                    
                TextColumn::make('taskType.name')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (Task $record): string => $record->taskType->color ?? 'primary')
                    ->width('120px'),

                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold')
                    ->tooltip(fn (Task $record): string => $record->title),

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
                    ->width('100px'),

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
                    ->width('120px'),

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
                    ->width('100px'),

                TextColumn::make('assignedUser.name')
                    ->label('Zugewiesen')
                    ->limit(20)
                    ->placeholder('Nicht zugewiesen')
                    ->tooltip(fn (Task $record): ?string => $record->assignedUser?->name),

                TextColumn::make('customer.company_name')
                    ->label('Kunde')
                    ->limit(20)
                    ->placeholder('Kein Kunde')
                    ->tooltip(fn (Task $record): ?string => $record->customer?->company_name),
            ])
            ->actions([
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
            ->emptyStateHeading('Keine wichtigen Aufgaben')
            ->emptyStateDescription('Alle wichtigen Aufgaben sind erledigt oder es gibt keine fälligen Aufgaben.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}