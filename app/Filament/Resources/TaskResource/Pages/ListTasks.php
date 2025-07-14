<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Form;

class ListTasks extends ListRecords implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = TaskResource::class;
    
    protected static string $view = 'filament.resources.task-resource.pages.list-tasks';

    public bool $showStatistics = false;
    public bool $showBoard = false;

    public function mount(): void
    {
        parent::mount();
        // Prüfe URL-Parameter oder Session für den aktuellen Zustand
        $this->showStatistics = request()->get('statistics', false);
        $this->showBoard = request()->get('board', false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleBoard')
                ->label(fn() => $this->showBoard ? 'Liste anzeigen' : 'Board anzeigen')
                ->icon(fn() => $this->showBoard ? 'heroicon-o-list-bullet' : 'heroicon-o-squares-2x2')
                ->color('primary')
                ->action(function () {
                    $this->showBoard = !$this->showBoard;
                    $this->showStatistics = false;
                }),
                
            Actions\Action::make('toggleStatistics')
                ->label(fn() => $this->showStatistics ? 'Aufgaben anzeigen' : 'Statistik anzeigen')
                ->icon(fn() => $this->showStatistics ? 'heroicon-o-list-bullet' : 'heroicon-o-chart-bar')
                ->color('gray')
                ->action(function () {
                    $this->showStatistics = !$this->showStatistics;
                    $this->showBoard = false;
                }),
            
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        if ($this->showStatistics) {
            return 'Aufgaben Statistiken';
        } elseif ($this->showBoard) {
            return 'Aufgaben Board';
        }
        return 'Aufgaben';
    }

    public function getStatusColumns(): array
    {
        // Spalten in logischer Workflow-Reihenfolge von links nach rechts
        $statusConfig = [
            'open' => ['label' => 'Offen', 'color' => 'gray'],
            'in_progress' => ['label' => 'In Bearbeitung', 'color' => 'blue'],
            'waiting_external' => ['label' => 'Warte auf Extern', 'color' => 'yellow'],
            'waiting_internal' => ['label' => 'Warte auf Intern', 'color' => 'purple'],
            'completed' => ['label' => 'Abgeschlossen', 'color' => 'green'],
            'cancelled' => ['label' => 'Abgebrochen', 'color' => 'red'],
        ];

        $columns = [];
        
        foreach ($statusConfig as $status => $config) {
            $tasks = TaskResource::getEloquentQuery()
                ->where('status', $status)
                ->with(['taskType', 'assignedUser', 'customer', 'supplier'])
                ->orderBy('due_date', 'asc')
                ->orderBy('priority', 'desc')
                ->get();

            $columns[$status] = [
                'label' => $config['label'],
                'color' => $config['color'],
                'count' => $tasks->count(),
                'tasks' => $tasks,
            ];
        }

        return $columns;
    }

    public function getStatistics(): array
    {
        $userId = auth()->id();
        
        // Basis-Query für alle Aufgaben
        $allTasksQuery = TaskResource::getEloquentQuery();
        
        // Basis-Query für meine Aufgaben
        $myTasksQuery = TaskResource::getEloquentQuery()->where(function ($q) use ($userId) {
            $q->where('assigned_to', $userId)
              ->orWhere('owner_id', $userId)
              ->orWhere('created_by', $userId);
        });
        
        return [
            'overview' => [
                'total_tasks' => (clone $allTasksQuery)->count(),
                'my_tasks' => (clone $myTasksQuery)->count(),
                'open_tasks' => (clone $allTasksQuery)->where('status', 'open')->count(),
                'in_progress_tasks' => (clone $allTasksQuery)->where('status', 'in_progress')->count(),
                'completed_tasks' => (clone $allTasksQuery)->where('status', 'completed')->count(),
                'overdue_tasks' => (clone $allTasksQuery)->overdue()->count(),
                'due_today' => (clone $allTasksQuery)->dueToday()->count(),
                'high_priority_tasks' => (clone $allTasksQuery)->highPriority()->count(),
            ],
            'my_tasks' => [
                'total' => (clone $myTasksQuery)->count(),
                'open' => (clone $myTasksQuery)->where('status', 'open')->count(),
                'in_progress' => (clone $myTasksQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $myTasksQuery)->where('status', 'completed')->count(),
                'overdue' => (clone $myTasksQuery)->overdue()->count(),
                'due_today' => (clone $myTasksQuery)->dueToday()->count(),
                'assigned_to_me' => TaskResource::getEloquentQuery()->where('assigned_to', $userId)->count(),
                'owned_by_me' => TaskResource::getEloquentQuery()->where('owner_id', $userId)->count(),
                'created_by_me' => TaskResource::getEloquentQuery()->where('created_by', $userId)->count(),
            ],
            'priority_distribution' => $this->getPriorityDistribution($allTasksQuery),
            'status_distribution' => $this->getStatusDistribution($allTasksQuery),
            'task_types' => $this->getTaskTypeDistribution($allTasksQuery),
            'time_tracking' => $this->getTimeTrackingStats($allTasksQuery),
            'productivity' => $this->getProductivityStats($myTasksQuery),
            'team_stats' => $this->getTeamStats(),
        ];
    }

    private function getPriorityDistribution($query): array
    {
        $total = (clone $query)->count();
        if ($total === 0) return [];
        
        $priorities = (clone $query)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        
        $distribution = [];
        $priorityLabels = [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'urgent' => 'Dringend'
        ];
        
        foreach ($priorityLabels as $priority => $label) {
            $count = $priorities[$priority] ?? 0;
            $percentage = round(($count / $total) * 100, 1);
            $distribution[$priority] = [
                'label' => $label,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        
        return $distribution;
    }

    private function getStatusDistribution($query): array
    {
        $total = (clone $query)->count();
        if ($total === 0) return [];
        
        $statuses = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $distribution = [];
        $statusLabels = [
            'open' => 'Offen',
            'in_progress' => 'In Bearbeitung',
            'waiting_external' => 'Warte auf Extern',
            'waiting_internal' => 'Warte auf Intern',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgebrochen'
        ];
        
        foreach ($statusLabels as $status => $label) {
            $count = $statuses[$status] ?? 0;
            $percentage = round(($count / $total) * 100, 1);
            $distribution[$status] = [
                'label' => $label,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        
        return $distribution;
    }

    private function getTaskTypeDistribution($query): array
    {
        $taskTypes = (clone $query)
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->selectRaw('task_types.name, task_types.color, COUNT(*) as count')
            ->groupBy('task_types.id', 'task_types.name', 'task_types.color')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        
        return $taskTypes;
    }

    private function getTimeTrackingStats($query): array
    {
        $tasks = (clone $query)
            ->whereNotNull('estimated_minutes')
            ->orWhereNotNull('actual_minutes')
            ->get();
        
        $totalEstimated = $tasks->sum('estimated_minutes');
        $totalActual = $tasks->sum('actual_minutes');
        $tasksWithEstimate = $tasks->whereNotNull('estimated_minutes')->count();
        $tasksWithActual = $tasks->whereNotNull('actual_minutes')->count();
        
        $avgEstimated = $tasksWithEstimate > 0 ? round($totalEstimated / $tasksWithEstimate) : 0;
        $avgActual = $tasksWithActual > 0 ? round($totalActual / $tasksWithActual) : 0;
        
        $accuracy = 0;
        if ($totalEstimated > 0 && $totalActual > 0) {
            $accuracy = round((min($totalEstimated, $totalActual) / max($totalEstimated, $totalActual)) * 100, 1);
        }
        
        return [
            'total_estimated_hours' => round($totalEstimated / 60, 1),
            'total_actual_hours' => round($totalActual / 60, 1),
            'avg_estimated_minutes' => $avgEstimated,
            'avg_actual_minutes' => $avgActual,
            'estimation_accuracy' => $accuracy,
            'tasks_with_estimates' => $tasksWithEstimate,
            'tasks_with_actual' => $tasksWithActual,
        ];
    }

    private function getProductivityStats($query): array
    {
        $now = now();
        
        // Aufgaben der letzten 30 Tage
        $last30Days = (clone $query)
            ->where('completed_at', '>=', $now->copy()->subDays(30))
            ->where('status', 'completed')
            ->count();
        
        // Aufgaben diese Woche
        $thisWeek = (clone $query)
            ->whereBetween('completed_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
            ->where('status', 'completed')
            ->count();
        
        // Aufgaben heute
        $today = (clone $query)
            ->whereDate('completed_at', $now->toDateString())
            ->where('status', 'completed')
            ->count();
        
        // Durchschnittliche Bearbeitungszeit
        $completedTasks = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->select('created_at', 'completed_at')
            ->get();
        
        $avgCompletionTime = 'N/A';
        if ($completedTasks->isNotEmpty()) {
            $totalHours = $completedTasks->sum(function ($task) {
                return $task->created_at->diffInHours($task->completed_at);
            });
            $avgHours = $totalHours / $completedTasks->count();
            
            if ($avgHours < 24) {
                $avgCompletionTime = round($avgHours, 1) . ' Std';
            } else {
                $avgCompletionTime = round($avgHours / 24, 1) . ' Tage';
            }
        }
        
        return [
            'completed_last_30_days' => $last30Days,
            'completed_this_week' => $thisWeek,
            'completed_today' => $today,
            'avg_completion_time' => $avgCompletionTime,
            'completion_rate' => $this->getCompletionRate($query),
        ];
    }

    private function getCompletionRate($query): float
    {
        $total = (clone $query)->count();
        if ($total === 0) return 0;
        
        $completed = (clone $query)->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    private function getTeamStats(): array
    {
        // Top 5 aktivste Benutzer (nach Anzahl zugewiesener Aufgaben)
        $topAssignees = TaskResource::getEloquentQuery()
            ->join('users', 'tasks.assigned_to', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as task_count')
            ->groupBy('users.id', 'users.name')
            ->orderBy('task_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
        
        // Top 5 Aufgaben-Ersteller
        $topCreators = TaskResource::getEloquentQuery()
            ->join('users', 'tasks.created_by', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as task_count')
            ->groupBy('users.id', 'users.name')
            ->orderBy('task_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
        
        return [
            'top_assignees' => $topAssignees,
            'top_creators' => $topCreators,
        ];
    }

    /**
     * Listener für das Livewire-Event, wenn eine Aufgabe verschoben wird.
     */
    public function onTaskDropped($taskId, $newStatus, $fromStatus, $orderedIds): void
    {
        // Finde die Aufgabe
        $task = \App\Models\Task::find($taskId);

        if ($task) {
            // Aktualisiere den Status
            $task->status = $newStatus;
            
            // Setze completed_at, wenn der neue Status 'completed' ist
            if ($newStatus === 'completed' && $task->completed_at === null) {
                $task->completed_at = now();
            } elseif ($newStatus !== 'completed') {
                $task->completed_at = null;
            }
            
            $task->save();

            // Optional: Logik zur Neuordnung basierend auf $orderedIds
            // ...
        }

        // Neu rendern, um die Änderungen zu übernehmen (optional, aber oft nützlich)
        $this->resetPage();
    }

    public function taskAction(): Actions\Action
    {
        return Actions\EditAction::make('edit')
            ->modalWidth('4xl')
            ->model(Task::class);
    }

    public function mountTaskAction(string $taskId)
    {
        $this->mountAction('edit', ['record' => $taskId]);
    }

}
