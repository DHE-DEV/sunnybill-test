<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\TaskHistory;
use App\Models\TaskReadStatus;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Mail;
use App\Mail\TaskNoteMention;
use Filament\Notifications\Notification;

class ListTasks extends ListRecords implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = TaskResource::class;
    
    protected static string $view = 'filament.resources.task-resource.pages.list-tasks';

    // Event-Listener für JavaScript-Events
    protected $listeners = [
        'addNote' => 'addNote',
        'task-updated' => '$refresh',
        'console-log' => 'handleConsoleLog'
    ];

    public bool $showStatistics = false;
    public bool $showBoard = false;
    public ?int $selectedTaskId = null;
    public bool $showEditModal = false;
    public ?Task $editingTask = null;
    
    // Form fields for editing
    public string $editTitle = '';
    public string $editDescription = '';
    public string $editStatus = '';
    public string $editPriority = '';
    public ?string $editDueDate = null;
    public $editTaskTypeId = null;
    public $editAssignedTo = null;
    public $editOwnerId = null;
    public $editSolarPlantId = null;
    
    // Notes modal properties
    public bool $showNotesModal = false;
    public ?Task $notesTask = null;
    public string $newNoteContent = '';
    
    // History modal properties
    public bool $showHistoryModal = false;
    public ?Task $historyTask = null;
    
    // Details modal properties
    public bool $showDetailsModal = false;
    public ?Task $detailsTask = null;
    
    // Filter properties
    public string $filterAssignment = 'all'; // all, assigned_to_me, owned_by_me, my_tasks
    public array $selectedStatuses = []; // Array für mehrere Status-Filter
    public string $searchQuery = ''; // Suchfeld für Titel und Aufgabennummer
    public string $solarPlantSearch = ''; // Suchfeld für Solaranlagen
    public array $selectedPriorities = []; // Array für Prioritäts-Filter
    public array $selectedDueDates = []; // Array für Fälligkeits-Filter

    public function mount(): void
    {
        parent::mount();
        // Prüfe URL-Parameter oder Session für den aktuellen Zustand
        $this->showStatistics = request()->get('statistics', false);
        $this->showBoard = request()->get('board', true); // Standardmäßig Board anzeigen
        
        // Standardmäßig alle Status anzeigen (inklusive Wiederkehrend)
        $this->selectedStatuses = ['recurring', 'open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled'];
        
        // Standardmäßig alle Prioritäten anzeigen
        $this->selectedPriorities = ['low', 'medium', 'high', 'urgent', 'blocker'];
        
        // Standardmäßig alle Fälligkeiten anzeigen
        $this->selectedDueDates = ['overdue', 'today', 'next_7_days', 'next_30_days', 'no_due_date'];
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
            'recurring' => ['label' => 'Wiederkehrend', 'color' => 'emerald', 'special' => true],
            'open' => ['label' => 'Offen', 'color' => 'gray'],
            'in_progress' => ['label' => 'In Bearbeitung', 'color' => 'blue'],
            'waiting_external' => ['label' => 'Warte auf Extern', 'color' => 'yellow'],
            'waiting_internal' => ['label' => 'Warte auf Intern', 'color' => 'purple'],
            'completed' => ['label' => 'Abgeschlossen', 'color' => 'green'],
            'cancelled' => ['label' => 'Abgebrochen', 'color' => 'red'],
        ];

        $columns = [];
        
        foreach ($statusConfig as $status => $config) {
            // Prüfe ob dieser Status im Filter ausgewählt ist
            if (!in_array($status, $this->selectedStatuses)) {
                continue;
            }
            
            // Spezialbehandlung für Wiederkehrend-Spalte
            if ($status === 'recurring') {
                $query = TaskResource::getEloquentQuery()
                    ->where('is_recurring', true)
                    ->whereNull('deleted_at')
                    ->with(['taskType', 'assignedUser', 'owner', 'customer', 'supplier', 'solarPlant']);
            } else {
                // WICHTIG: Ausschluss von wiederkehrenden Tasks aus normalen Spalten
                $query = TaskResource::getEloquentQuery()
                    ->where('status', $status)
                    ->where('is_recurring', false) // Nur nicht-wiederkehrende Tasks
                    ->whereNull('deleted_at')
                    ->with(['taskType', 'assignedUser', 'owner', 'customer', 'supplier', 'solarPlant']);
            }
            
            // Zuweisungsfilter anwenden
            $this->applyAssignmentFilter($query);
            
            // Suchfilter anwenden
            $this->applySearchFilter($query);
            
            // Solaranlagen-Filter anwenden
            $this->applySolarPlantFilter($query);
            
            // Prioritätsfilter anwenden
            $this->applyPriorityFilter($query);
            
            // Fälligkeitsfilter anwenden
            $this->applyDueDateFilter($query);
            
            $tasks = $query
                ->orderByRaw('CASE WHEN priority = "blocker" THEN 0 ELSE 1 END')
                ->orderBy('sort_order', 'asc')
                ->orderBy('due_date', 'asc')
                ->orderBy('priority', 'desc')
                ->get();

            $columns[$status] = [
                'label' => $config['label'],
                'color' => $config['color'],
                'count' => $tasks->count(),
                'tasks' => $tasks,
                'special' => $config['special'] ?? false,
            ];
        }

        return $columns;
    }
    
    /**
     * Wendet den Zuweisungsfilter auf die Query an
     */
    private function applyAssignmentFilter($query): void
    {
        $userId = auth()->id();
        
        switch ($this->filterAssignment) {
            case 'assigned_to_me':
                $query->where('assigned_to', $userId);
                break;
                
            case 'owned_by_me':
                $query->where('owner_id', $userId);
                break;
                
            case 'my_tasks':
                $query->where(function ($q) use ($userId) {
                    $q->where('assigned_to', $userId)
                      ->orWhere('owner_id', $userId)
                      ->orWhere('created_by', $userId);
                });
                break;
                
            case 'all':
            default:
                // Keine Filterung
                break;
        }
    }
    
    /**
     * Filtert nach Zuweisungen
     */
    public function filterByAssignment($assignment): void
    {
        $this->filterAssignment = $assignment;
    }
    
    /**
     * Filtert nach Status (Toggle)
     */
    public function toggleStatusFilter($status): void
    {
        if (in_array($status, $this->selectedStatuses)) {
            $this->selectedStatuses = array_diff($this->selectedStatuses, [$status]);
        } else {
            $this->selectedStatuses = array_merge($this->selectedStatuses, [$status]);
        }
    }
    
    /**
     * Filtert nach Priorität (Toggle)
     */
    public function togglePriorityFilter($priority): void
    {
        if (in_array($priority, $this->selectedPriorities)) {
            $this->selectedPriorities = array_diff($this->selectedPriorities, [$priority]);
        } else {
            $this->selectedPriorities = array_merge($this->selectedPriorities, [$priority]);
        }
    }
    
    /**
     * Filtert nach Fälligkeit (Toggle)
     */
    public function toggleDueDateFilter($dueDate): void
    {
        if (in_array($dueDate, $this->selectedDueDates)) {
            $this->selectedDueDates = array_diff($this->selectedDueDates, [$dueDate]);
        } else {
            $this->selectedDueDates = array_merge($this->selectedDueDates, [$dueDate]);
        }
    }
    
    /**
     * Wendet den Suchfilter auf die Query an
     */
    private function applySearchFilter($query): void
    {
        if (!empty(trim($this->searchQuery))) {
            $searchTerm = trim($this->searchQuery);
            
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('task_number', 'LIKE', "%{$searchTerm}%");
            });
        }
    }
    
    /**
     * Wendet den Solaranlagen-Filter auf die Query an
     */
    private function applySolarPlantFilter($query): void
    {
        if (!empty(trim($this->solarPlantSearch))) {
            $searchTerm = trim($this->solarPlantSearch);
            
            $query->where(function ($q) use ($searchTerm) {
                // Zeige Tasks für spezifische Solaranlagen
                $q->whereHas('solarPlant', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('plant_number', 'LIKE', "%{$searchTerm}%");
                })
                // UND auch Tasks die für "alle Solaranlagen" gelten
                ->orWhere('applies_to_all_solar_plants', true);
            });
        }
    }
    
    /**
     * Wendet den Prioritätsfilter auf die Query an
     */
    private function applyPriorityFilter($query): void
    {
        if (!empty($this->selectedPriorities) && count($this->selectedPriorities) < 5) {
            $query->whereIn('priority', $this->selectedPriorities);
        }
    }
    
    /**
     * Wendet den Fälligkeitsfilter auf die Query an
     */
    private function applyDueDateFilter($query): void
    {
        if (!empty($this->selectedDueDates) && count($this->selectedDueDates) < 5) {
            $query->where(function ($q) {
                $now = now()->startOfDay();
                $conditions = [];
                
                foreach ($this->selectedDueDates as $dueDateFilter) {
                    switch ($dueDateFilter) {
                        case 'overdue':
                            $conditions[] = function ($subQuery) use ($now) {
                                $subQuery->where('due_date', '<', $now)
                                        ->whereNotNull('due_date');
                            };
                            break;
                            
                        case 'today':
                            $conditions[] = function ($subQuery) use ($now) {
                                $subQuery->whereDate('due_date', $now->toDateString());
                            };
                            break;
                            
                        case 'next_7_days':
                            $conditions[] = function ($subQuery) use ($now) {
                                $subQuery->whereBetween('due_date', [
                                    $now->copy()->addDay()->startOfDay(),
                                    $now->copy()->addDays(7)->endOfDay()
                                ]);
                            };
                            break;
                            
                        case 'next_30_days':
                            $conditions[] = function ($subQuery) use ($now) {
                                $subQuery->whereBetween('due_date', [
                                    $now->copy()->addDays(8)->startOfDay(),
                                    $now->copy()->addDays(30)->endOfDay()
                                ]);
                            };
                            break;
                            
                        case 'no_due_date':
                            $conditions[] = function ($subQuery) {
                                $subQuery->whereNull('due_date');
                            };
                            break;
                    }
                }
                
                // Füge die erste Bedingung hinzu
                if (!empty($conditions)) {
                    $q->where($conditions[0]);
                    
                    // Füge weitere Bedingungen mit OR hinzu
                    for ($i = 1; $i < count($conditions); $i++) {
                        $q->orWhere($conditions[$i]);
                    }
                }
            });
        }
    }
    
    /**
     * Setzt alle Filter zurück
     */
    public function resetFilters(): void
    {
        $this->filterAssignment = 'all';
        $this->selectedStatuses = ['recurring', 'open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled'];
        $this->selectedPriorities = ['low', 'medium', 'high', 'urgent', 'blocker'];
        $this->selectedDueDates = ['overdue', 'today', 'next_7_days', 'next_30_days', 'no_due_date'];
        $this->searchQuery = '';
        $this->solarPlantSearch = '';
    }
    
    /**
     * Getter für die verfügbaren Zuweisungsfilter
     */
    public function getAssignmentFiltersProperty(): array
    {
        return [
            'all' => 'Alle Aufgaben',
            'assigned_to_me' => 'Mir zugewiesen',
            'owned_by_me' => 'In meinem Besitz',
            'my_tasks' => 'Meine Aufgaben (alle)'
        ];
    }
    
    /**
     * Getter für die verfügbaren Status-Filter
     */
    public function getAvailableStatusesProperty(): array
    {
        return [
            'recurring' => 'Wiederkehrend',
            'open' => 'Offen',
            'in_progress' => 'In Bearbeitung',
            'waiting_external' => 'Warte auf Extern',
            'waiting_internal' => 'Warte auf Intern',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgebrochen'
        ];
    }
    
    /**
     * Getter für die verfügbaren Prioritäts-Filter
     */
    public function getAvailablePrioritiesProperty(): array
    {
        return [
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
            'blocker' => 'Blocker'
        ];
    }
    
    /**
     * Getter für die verfügbaren Fälligkeits-Filter
     */
    public function getAvailableDueDatesProperty(): array
    {
        return [
            'overdue' => 'Überfällig',
            'today' => 'Heute',
            'next_7_days' => 'Nächste 7 Tage',
            'next_30_days' => 'Nächste 30 Tage',
            'no_due_date' => 'Ohne Fälligkeitsdatum'
        ];
    }

    public function getStatistics(): array
    {
        $userId = auth()->id();
        
        // Basis-Query für alle Aufgaben
        $allTasksQuery = TaskResource::getEloquentQuery()->whereNull('deleted_at');
        
        // Basis-Query für meine Aufgaben
        $myTasksQuery = TaskResource::getEloquentQuery()->whereNull('deleted_at')->where(function ($q) use ($userId) {
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
            'urgent' => 'Dringend',
            'blocker' => 'Blocker'
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
            ->whereNull('deleted_at')
            ->join('users', 'tasks.assigned_to', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as task_count')
            ->groupBy('users.id', 'users.name')
            ->orderBy('task_count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
        
        // Top 5 Aufgaben-Ersteller
        $topCreators = TaskResource::getEloquentQuery()
            ->whereNull('deleted_at')
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
    public function onTaskDropped($taskId, $newStatus, $fromStatus, $orderedIds = []): void
    {
        // Finde die Aufgabe
        $task = \App\Models\Task::find($taskId);

        if ($task) {
            $oldStatus = $task->status;
            
            // Aktualisiere den Status
            $task->status = $newStatus;
            
            // Setze completed_at, wenn der neue Status 'completed' ist
            if ($newStatus === 'completed' && $task->completed_at === null) {
                $task->completed_at = now();
            } elseif ($newStatus !== 'completed') {
                $task->completed_at = null;
            }
            
            $task->save();
            
            // Historie für Status-Änderung
            TaskHistory::logFieldChange($task, auth()->id(), 'status', $oldStatus, $newStatus);

            // Status-Änderungs-Benachrichtigung senden
            $this->sendStatusChangeNotification($task, $oldStatus, $newStatus);

            // Reihenfolge der Aufgaben in der Spalte aktualisieren
            $this->updateTaskOrderFromDrop($newStatus, $orderedIds);
        }

        // Neu rendern, um die Änderungen zu übernehmen
        $this->dispatch('task-updated');
    }

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make('editTask')
                ->record(fn (array $arguments) => Task::find($arguments['record']))
                ->modalWidth('4xl')
                ->form(fn (Form $form) => TaskResource::form($form))
                ->successNotificationTitle('Aufgabe erfolgreich aktualisiert')
                ->visible(false), // Versteckt, da wir es programmatisch aufrufen
        ];
    }

    public function editTaskById($taskId)
    {
        $this->editingTask = Task::find($taskId);
        
        if ($this->editingTask) {
            // Formularfelder mit Task-Daten füllen
            $this->editTitle = $this->editingTask->title ?? '';
            $this->editDescription = $this->editingTask->description ?? '';
            $this->editStatus = $this->editingTask->status ?? 'open';
            $this->editPriority = $this->editingTask->priority ?? 'medium';
            $this->editDueDate = $this->editingTask->due_date ? $this->editingTask->due_date->format('Y-m-d') : null;
            $this->editTaskTypeId = $this->editingTask->task_type_id;
            $this->editAssignedTo = $this->editingTask->assigned_to;
            $this->editOwnerId = $this->editingTask->owner_id;
            
            // Korrekte Behandlung der Solaranlagen-Auswahl
            if ($this->editingTask->applies_to_all_solar_plants) {
                $this->editSolarPlantId = 'all';
            } else {
                $this->editSolarPlantId = $this->editingTask->solar_plant_id;
            }
            
            $this->showEditModal = true;
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingTask = null;
        
        // Formularfelder zurücksetzen
        $this->editTitle = '';
        $this->editDescription = '';
        $this->editStatus = '';
        $this->editPriority = '';
        $this->editDueDate = null;
        $this->editTaskTypeId = null;
        $this->editAssignedTo = null;
        $this->editOwnerId = null;
    }

    public function saveTask()
    {
        if ($this->editingTask) {
            // Alte Werte für Historie speichern
            $oldValues = $this->editingTask->getOriginal();
            
            // Bestehende Task mit Formulardaten aktualisieren
            $this->editingTask->title = $this->editTitle;
            $this->editingTask->description = $this->editDescription;
            
            // Behandle "Wiederkehrend" Status
            if ($this->editStatus === 'recurring') {
                $this->editingTask->is_recurring = true;
                // Behalte den ursprünglichen Status bei oder setze auf 'open' falls noch keiner vorhanden
                if (!$this->editingTask->status || $this->editingTask->status === 'recurring') {
                    $this->editingTask->status = 'open';
                }
            } else {
                $this->editingTask->status = $this->editStatus;
                $this->editingTask->is_recurring = false;
            }
            
            $this->editingTask->priority = $this->editPriority;
            $this->editingTask->due_date = $this->editDueDate ? \Carbon\Carbon::parse($this->editDueDate) : null;
            $this->editingTask->task_type_id = $this->editTaskTypeId ?: null;
            $this->editingTask->assigned_to = $this->editAssignedTo ?: null;
            $this->editingTask->owner_id = $this->editOwnerId ?: null;
            
            // Behandle "Alle Solaranlagen" Auswahl
            if ($this->editSolarPlantId === 'all') {
                $this->editingTask->solar_plant_id = null;
                $this->editingTask->applies_to_all_solar_plants = true;
            } else {
                $this->editingTask->solar_plant_id = $this->editSolarPlantId ?: null;
                $this->editingTask->applies_to_all_solar_plants = false;
            }
            
            $this->editingTask->save();
            
            // Historie für geänderte Felder erstellen
            $this->logTaskChanges($this->editingTask, $oldValues);
            
            // E-Mail-Benachrichtigungen für Änderungen senden
            $this->sendTaskChangeNotifications($this->editingTask, $oldValues);
        } else {
            // Neue Aufgabe erstellen
            $this->createTask();
            return; // createTask() schließt bereits das Modal
        }

        $this->closeEditModal();
        
        // Board neu laden
        $this->dispatch('task-updated');
    }

    public function getTaskTypesProperty()
    {
        return \App\Models\TaskType::active()->ordered()->get();
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get();
    }

    public function getSolarPlantsProperty()
    {
        return \App\Models\SolarPlant::orderBy('name')->get();
    }

    public function addTaskToColumn($status)
    {
        // Modal für neue Aufgabe öffnen
        $this->editingTask = null; // Keine bestehende Aufgabe
        $this->showEditModal = true;
        
        // Formularfelder für neue Aufgabe zurücksetzen
        $this->editTitle = '';
        $this->editDescription = '';
        $this->editStatus = $status; // Status der Spalte vorauswählen
        $this->editPriority = 'medium'; // Standard-Priorität
        $this->editDueDate = null;
        $this->editTaskTypeId = null;
        $this->editAssignedTo = null;
        $this->editOwnerId = null;
        $this->editSolarPlantId = null; // Solaranlagen-Feld zurücksetzen
    }

    public function createTask()
    {
        // Behandle "Wiederkehrend" Status
        $actualStatus = $this->editStatus;
        $isRecurring = false;
        
        if ($this->editStatus === 'recurring') {
            $isRecurring = true;
            $actualStatus = 'open'; // Wiederkehrende Aufgaben starten als "Offen"
        }

        // Alle anderen Aufgaben im gleichen Status um 1 nach unten verschieben
        \App\Models\Task::where('status', $actualStatus)
            ->increment('sort_order');

        // Behandle "Alle Solaranlagen" Auswahl
        $solarPlantId = null;
        $appliesToAllSolarPlants = false;
        
        if ($this->editSolarPlantId === 'all') {
            $solarPlantId = null;
            $appliesToAllSolarPlants = true;
        } else {
            $solarPlantId = $this->editSolarPlantId ?: null;
            $appliesToAllSolarPlants = false;
        }

        // Neue Aufgabe erstellen
        $task = \App\Models\Task::create([
            'title' => $this->editTitle,
            'description' => $this->editDescription,
            'status' => $actualStatus,
            'is_recurring' => $isRecurring,
            'priority' => $this->editPriority,
            'due_date' => $this->editDueDate ? \Carbon\Carbon::parse($this->editDueDate) : null,
            'task_type_id' => $this->editTaskTypeId ?: null,
            'assigned_to' => $this->editAssignedTo ?: null,
            'owner_id' => $this->editOwnerId ?: null,
            'solar_plant_id' => $solarPlantId,
            'applies_to_all_solar_plants' => $appliesToAllSolarPlants,
            'created_by' => auth()->id(),
            'sort_order' => 1, // Neue Aufgaben immer ganz oben
        ]);

        // Historie für Task-Erstellung
        TaskHistory::logTaskCreation($task, auth()->id());

        // E-Mail-Benachrichtigung für neue Aufgabe senden
        $this->sendNewTaskNotifications($task);

        $this->closeEditModal();
        
        // Board neu laden
        $this->dispatch('task-updated');
    }

    // Notes Modal Methods
    public function openNotesModal($taskId)
    {
        $this->notesTask = Task::with(['notes.user'])->find($taskId);
        $this->newNoteContent = '';
        $this->showNotesModal = true;
        
        // Event für JavaScript auslösen, damit Rich Text Editor initialisiert wird
        $this->dispatch('notesModalOpened', [
            'task_id' => $taskId,
            'task_title' => $this->notesTask ? $this->notesTask->title : 'Unbekannte Aufgabe'
        ]);
    }

    public function closeNotesModal()
    {
        $this->showNotesModal = false;
        $this->notesTask = null;
        $this->newNoteContent = '';
    }

    public function addNote()
    {
        if (!$this->notesTask || empty(trim($this->newNoteContent))) {
            \Log::info('ℹ️ Kanban: addNote abgebrochen - leerer Inhalt oder keine Task', [
                'has_task' => !!$this->notesTask,
                'content_empty' => empty(trim($this->newNoteContent)),
                'content_length' => strlen(trim($this->newNoteContent ?? ''))
            ]);
            return;
        }

        $content = trim($this->newNoteContent);
        
        \Log::info('📝 Kanban: Neue Notiz wird erstellt', [
            'task_id' => $this->notesTask->id,
            'task_title' => $this->notesTask->title,
            'content' => $content,
            'author_id' => auth()->id(),
            'author_name' => auth()->user()->name
        ]);
        
        // JavaScript Console Log für bessere Debugging-Erfahrung
        $this->dispatch('console-log', [
            'type' => 'info',
            'message' => '📝 Neue Notiz wird erstellt',
            'data' => [
                'task_id' => $this->notesTask->id,
                'task_title' => $this->notesTask->title,
                'content' => $content,
                'author' => auth()->user()->name
            ]
        ]);
        
        // @mentions extrahieren
        $mentionedUsernames = $this->extractMentions($content);
        $mentionedUsers = collect(); // Als Collection initialisieren
        
        \Log::info('🔍 Kanban: @mentions extrahiert', [
            'task_id' => $this->notesTask->id,
            'mentioned_usernames' => $mentionedUsernames,
            'count' => count($mentionedUsernames),
            'content' => $content,
            'regex_pattern' => '/@([a-zA-ZäöüÄÖÜß]+(?:\s+[a-zA-ZäöüÄÖÜß]+)*)/u'
        ]);
        
        $this->dispatch('console-log', [
            'type' => 'info',
            'message' => '🔍 @mentions extrahiert',
            'data' => [
                'mentioned_usernames' => $mentionedUsernames,
                'count' => count($mentionedUsernames),
                'content' => $content,
                'content_length' => strlen($content),
                'regex_pattern' => '/@([a-zA-ZäöüÄÖÜß]+(?:\s+[a-zA-ZäöüÄÖÜß]+)*)/u',
                'raw_matches' => $this->debugExtractMentions($content)
            ]
        ]);
        
        if (!empty($mentionedUsernames)) {
            $mentionedUsers = User::whereIn('name', $mentionedUsernames)->get();
            
            // Zusätzliche Debug-Informationen für Case-Sensitivity und Exact-Matching
            $allUsersInDb = User::pluck('name')->toArray();
            $debugMatches = [];
            
            foreach ($mentionedUsernames as $mentionedName) {
                $exactMatch = User::where('name', $mentionedName)->first();
                $caseInsensitiveMatch = User::whereRaw('LOWER(name) = ?', [strtolower($mentionedName)])->first();
                
                $debugMatches[] = [
                    'searched_name' => $mentionedName,
                    'searched_name_length' => strlen($mentionedName),
                    'encoding' => mb_detect_encoding($mentionedName, 'UTF-8, ISO-8859-1', true),
                    'exact_match' => $exactMatch ? $exactMatch->name : null,
                    'case_insensitive_match' => $caseInsensitiveMatch ? $caseInsensitiveMatch->name : null,
                    'potential_matches' => array_filter($allUsersInDb, function($dbName) use ($mentionedName) {
                        return stripos($dbName, $mentionedName) !== false || stripos($mentionedName, $dbName) !== false;
                    })
                ];
            }
            
            \Log::info('👥 Kanban: Gefundene Benutzer für @mentions', [
                'task_id' => $this->notesTask->id,
                'mentioned_usernames' => $mentionedUsernames,
                'found_users' => $mentionedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                })->toArray(),
                'found_count' => $mentionedUsers->count(),
                'all_users_in_db' => $allUsersInDb,
                'debug_matches' => $debugMatches
            ]);
            
            $this->dispatch('console-log', [
                'type' => 'info',
                'message' => '👥 Benutzer für @mentions gefunden',
                'data' => [
                    'mentioned_usernames' => $mentionedUsernames,
                    'found_users' => $mentionedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ];
                    })->toArray(),
                    'found_count' => $mentionedUsers->count(),
                    'all_users_in_db' => $allUsersInDb,
                    'debug_matches' => $debugMatches
                ]
            ]);

            // Zusätzliche detaillierte Debug-Ausgabe für jede Suche
            foreach ($debugMatches as $index => $match) {
                $this->dispatch('console-log', [
                    'type' => 'warning',
                    'message' => "🔍 Debug-Match #{$index}: {$match['searched_name']}",
                    'data' => $match
                ]);
            }
        }

        // Notiz erstellen
        $note = TaskNote::create([
            'task_id' => $this->notesTask->id,
            'user_id' => auth()->id(),
            'content' => $content,
            'mentioned_users' => $mentionedUsers->pluck('id')->toArray(),
        ]);

        \Log::info('✅ Kanban: Notiz erfolgreich erstellt', [
            'note_id' => $note->id,
            'task_id' => $this->notesTask->id,
            'mentioned_users_count' => $mentionedUsers->count()
        ]);

        $this->dispatch('console-log', [
            'type' => 'success',
            'message' => '✅ Notiz erfolgreich erstellt',
            'data' => [
                'note_id' => $note->id,
                'task_id' => $this->notesTask->id,
                'mentioned_users_count' => $mentionedUsers->count()
            ]
        ]);

        // E-Mail-Benachrichtigungen an erwähnte Benutzer senden
        if ($mentionedUsers->isNotEmpty()) {
            \Log::info('📧 Kanban: Starte E-Mail-Benachrichtigungen', [
                'note_id' => $note->id,
                'task_id' => $this->notesTask->id,
                'users_to_notify' => $mentionedUsers->count()
            ]);

            $this->dispatch('console-log', [
                'type' => 'info',
                'message' => '📧 Starte E-Mail-Benachrichtigungen',
                'data' => [
                    'note_id' => $note->id,
                    'task_id' => $this->notesTask->id,
                    'users_to_notify' => $mentionedUsers->count()
                ]
            ]);

            $this->sendMentionNotifications($note, $mentionedUsers);
        } else {
            \Log::info('ℹ️ Kanban: Keine E-Mail-Benachrichtigungen erforderlich', [
                'note_id' => $note->id,
                'task_id' => $this->notesTask->id
            ]);

            $this->dispatch('console-log', [
                'type' => 'warning',
                'message' => 'ℹ️ Keine E-Mail-Benachrichtigungen erforderlich',
                'data' => [
                    'note_id' => $note->id,
                    'task_id' => $this->notesTask->id
                ]
            ]);
        }

        // Historie-Eintrag für hinzugefügte Notiz
        TaskHistory::logNoteAdded($this->notesTask, auth()->id());

        // Notizen neu laden
        $this->notesTask = Task::with(['notes.user'])->find($this->notesTask->id);
        $this->newNoteContent = '';
        
        \Log::info('🔄 Kanban: Notiz-Verarbeitung abgeschlossen', [
            'note_id' => $note->id,
            'task_id' => $this->notesTask->id
        ]);

        $this->dispatch('console-log', [
            'type' => 'success',
            'message' => '🔄 Notiz-Verarbeitung abgeschlossen',
            'data' => [
                'note_id' => $note->id,
                'task_id' => $this->notesTask->id
            ]
        ]);
        
        // Spezielles Event für Rich Text Editor Reinitialisierung
        $this->dispatch('noteAdded', [
            'note_id' => $note->id,
            'task_id' => $this->notesTask->id,
            'reinitialize_editor' => true
        ]);
        
        // Zusätzliches Event für Livewire-Hooks
        $this->dispatch('noteSaved');
    }
    
    /**
     * Extrahiert @mentions aus dem Text (maximal 2 Wörter pro Mention)
     * Unterstützt sowohl Plain Text als auch HTML-Inhalt
     */
    private function extractMentions(string $content): array
    {
        \Log::info('🔍 Kanban: Extrahiere @mentions aus Inhalt', [
            'raw_content' => $content,
            'content_length' => strlen($content),
            'is_html' => strpos($content, '<') !== false
        ]);

        // Entferne HTML-Tags für die Mention-Extraktion, behalte aber den Text
        $plainTextContent = strip_tags($content);
        
        \Log::info('🔍 Kanban: Nach HTML-Tag-Entfernung', [
            'plain_content' => $plainTextContent,
            'plain_length' => strlen($plainTextContent)
        ]);
        
        // Erweiterte Regex: maximal 2 Wörter nach @, stoppt bei Nicht-Buchstaben
        preg_match_all('/@([a-zA-ZäöüÄÖÜßÀ-ÿ]+(?:\s+[a-zA-ZäöüÄÖÜßÀ-ÿ]+)?)\b/u', $plainTextContent, $matches);
        
        $extractedMentions = array_map('trim', $matches[1]);
        
        \Log::info('✅ Kanban: @mentions extrahiert', [
            'extracted_mentions' => $extractedMentions,
            'count' => count($extractedMentions),
            'regex_matches' => $matches
        ]);
        
        return $extractedMentions;
    }
    
    /**
     * Debug-Funktion für @mentions-Extraktion
     */
    private function debugExtractMentions(string $content): array
    {
        $pattern = '/@([a-zA-ZäöüÄÖÜß]+(?:\s+[a-zA-ZäöüÄÖÜß]+)?)\b/u';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        return [
            'full_matches' => $matches,
            'pattern_explanation' => 'Regex erfasst maximal 2 Wörter nach @, dann Stopp bei Wortgrenze (\\b)',
            'content_analysis' => [
                'has_at_symbol' => strpos($content, '@') !== false,
                'at_positions' => array_keys(array_filter(str_split($content), fn($char) => $char === '@')),
                'content_length' => strlen($content),
                'content_preview' => mb_substr($content, 0, 100) . (strlen($content) > 100 ? '...' : ''),
                'encoding' => mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true)
            ]
        ];
    }
    
    /**
     * Sendet E-Mail-Benachrichtigungen an erwähnte Benutzer
     */
    private function sendMentionNotifications(TaskNote $note, $mentionedUsers): void
    {
        \Log::info('📧 Kanban: Versuche E-Mail-Benachrichtigungen zu senden', [
            'note_id' => $note->id,
            'task_id' => $note->task_id,
            'mentioned_users_count' => $mentionedUsers->count(),
            'author_id' => auth()->id(),
            'author_name' => auth()->user()->name
        ]);

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errorMessages = [];

        foreach ($mentionedUsers as $user) {
            // Nicht an sich selbst senden
            if ($user->id === auth()->id()) {
                \Log::info('⏭️ Kanban: Überspringe Selbst-Benachrichtigung', [
                    'note_id' => $note->id,
                    'author_id' => auth()->id()
                ]);
                $skippedCount++;
                continue;
            }
            
            try {
                \Log::info('📧 Kanban: Versuche E-Mail-Benachrichtigung zu senden', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'note_id' => $note->id,
                    'task_id' => $note->task_id
                ]);

                // E-Mail-Adresse validieren
                if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    \Log::warning('❌ Kanban: Ungültige E-Mail-Adresse für Mention-Benachrichtigung', [
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                    $errorCount++;
                    $errorMessages[] = "Ungültige E-Mail-Adresse für {$user->name}";
                    continue;
                }

                // Gmail Service verwenden
                $gmailService = app(\App\Services\GmailService::class);
                
                // E-Mail-Inhalt rendern
                $emailContent = view('emails.task-note-mention', [
                    'user' => $user,
                    'note' => $note,
                    'task' => $note->task,
                    'author' => auth()->user(),
                    'mentionedUser' => $user,
                    'taskUrl' => route('filament.admin.resources.tasks.index', [
                        'openNotes' => $note->task_id
                    ])
                ])->render();

                $subject = "Neue Notiz - Aufgabe - {$note->task->title}";

                // E-Mail über Gmail Service senden
                $result = $gmailService->sendEmail(
                    $user->email,
                    $subject,
                    $emailContent
                );

                if ($result['success'] ?? false) {
                    \Log::info('✅ Kanban: Task-Notiz Mention E-Mail erfolgreich gesendet', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'note_id' => $note->id,
                        'task_id' => $note->task_id,
                        'subject' => $subject,
                        'message_id' => $result['message_id'] ?? null
                    ]);
                    $successCount++;
                } else {
                    \Log::error('❌ Kanban: Task-Notiz Mention E-Mail konnte nicht gesendet werden', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'note_id' => $note->id,
                        'task_id' => $note->task_id,
                        'error' => $result['error'] ?? 'Unbekannter Fehler'
                    ]);
                    $errorCount++;
                    $errorMessages[] = "Fehler beim Senden an {$user->name}: " . ($result['error'] ?? 'Unbekannter Fehler');
                }

            } catch (\Exception $e) {
                \Log::error('❌ Kanban: Fehler beim Senden der Task-Notiz Mention E-Mail', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'note_id' => $note->id,
                    'task_id' => $note->task_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
                $errorMessages[] = "Fehler beim Senden an {$user->name}: " . $e->getMessage();
            }
        }

        \Log::info('✅ Kanban: @mentions Verarbeitung abgeschlossen', [
            'note_id' => $note->id,
            'total_users' => $mentionedUsers->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'skipped_count' => $skippedCount
        ]);

        // Filament-Notifications senden
        $this->sendNotificationFeedback($successCount, $errorCount, $skippedCount, $errorMessages, $mentionedUsers);
    }

    /**
     * Sendet Filament-Notifications über den E-Mail-Versand-Status
     */
    private function sendNotificationFeedback(int $successCount, int $errorCount, int $skippedCount, array $errorMessages, $mentionedUsers): void
    {
        $totalUsers = $mentionedUsers->count();
        $targetUsers = $totalUsers - $skippedCount; // Benutzer, an die tatsächlich versendet werden sollte

        if ($successCount > 0 && $errorCount === 0) {
            // Alle E-Mails erfolgreich versendet
            $message = $successCount === 1 
                ? 'E-Mail-Benachrichtigung erfolgreich versendet'
                : "{$successCount} E-Mail-Benachrichtigungen erfolgreich versendet";
            
            Notification::make()
                ->title('E-Mail-Benachrichtigungen versendet')
                ->body($message)
                ->success()
                ->icon('heroicon-o-envelope')
                ->send();
                
        } elseif ($successCount > 0 && $errorCount > 0) {
            // Teilweise erfolgreich
            $message = "{$successCount} von {$targetUsers} E-Mail-Benachrichtigungen erfolgreich versendet";
            
            Notification::make()
                ->title('E-Mail-Benachrichtigungen teilweise versendet')
                ->body($message)
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->send();
                
        } elseif ($successCount === 0 && $errorCount > 0) {
            // Alle E-Mails fehlgeschlagen
            $message = $errorCount === 1 
                ? 'E-Mail-Benachrichtigung konnte nicht versendet werden'
                : "{$errorCount} E-Mail-Benachrichtigungen konnten nicht versendet werden";
            
            Notification::make()
                ->title('E-Mail-Benachrichtigungen fehlgeschlagen')
                ->body($message)
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->send();
                
        } elseif ($totalUsers > 0 && $targetUsers === 0) {
            // Nur Selbst-Erwähnungen
            Notification::make()
                ->title('Keine E-Mail-Benachrichtigungen')
                ->body('Sie haben sich selbst erwähnt - keine E-Mail-Benachrichtigung erforderlich')
                ->info()
                ->icon('heroicon-o-information-circle')
                ->send();
        }

        // Detaillierte Fehlermeldungen bei Problemen
        if ($errorCount > 0 && !empty($errorMessages)) {
            $detailedMessage = implode("\n", array_slice($errorMessages, 0, 3)); // Zeige max. 3 Fehler
            
            if (count($errorMessages) > 3) {
                $detailedMessage .= "\n... und " . (count($errorMessages) - 3) . " weitere Fehler";
            }
            
            Notification::make()
                ->title('Detaillierte Fehlermeldungen')
                ->body($detailedMessage)
                ->danger()
                ->icon('heroicon-o-exclamation-triangle')
                ->persistent()
                ->send();
        }
    }

    public function getNotesProperty()
    {
        if (!$this->notesTask) {
            return collect();
        }

        return $this->notesTask->notes()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // History Modal Methods
    public function openHistoryModal($taskId)
    {
        $this->historyTask = Task::with(['history.user'])->find($taskId);
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyTask = null;
    }

    public function getHistoryProperty()
    {
        if (!$this->historyTask) {
            return collect();
        }

        return $this->historyTask->history()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Details Modal Methods
    public function openDetailsModal($taskId)
    {
        $this->detailsTask = Task::with(['taskType', 'assignedUser', 'owner', 'customer', 'supplier', 'solarPlant', 'parentTask', 'subtasks', 'creator'])->find($taskId);
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->detailsTask = null;
    }

    // Mark as read methods
    public function markAsRead($taskId)
    {
        $task = Task::find($taskId);
        if ($task) {
            TaskReadStatus::markAllAsRead($task, auth()->id());
        }
    }

    // Helper methods for checking unread status
    public function hasUnreadNotes($task)
    {
        return TaskReadStatus::hasUnreadNotes($task, auth()->id());
    }

    public function hasUnreadHistory($task)
    {
        return TaskReadStatus::hasUnreadHistory($task, auth()->id());
    }

    // Get due date color based on days remaining
    public function getDueDateColor($dueDate)
    {
        if (!$dueDate) {
            return 'text-gray-500';
        }

        $now = now()->startOfDay();
        $due = \Carbon\Carbon::parse($dueDate)->startOfDay();
        $diffInDays = $now->diffInDays($due, false);

        if ($diffInDays < 0) {
            // Overdue - red
            return 'text-red-500';
        } elseif ($diffInDays == 0) {
            // Due today - orange
            return 'text-orange-500';
        } elseif ($diffInDays <= 7) {
            // Due within a week - blue
            return 'text-blue-500';
        } else {
            // Due later - gray
            return 'text-gray-500';
        }
    }

    // Get due date text
    public function getDueDateText($task)
    {
        $dueDate = $task->due_date;
        $status = $task->status;
        
        // Spezielle Behandlung für abgeschlossene/abgebrochene Aufgaben
        if (in_array($status, ['completed', 'cancelled'])) {
            $result = '';
            
            // Fälligkeitsdatum anzeigen
            if ($dueDate) {
                $result .= 'Fällig: ' . \Carbon\Carbon::parse($dueDate)->format('d.m.Y');
            }
            
            // Erledigungsdatum anzeigen
            if ($task->completed_at) {
                if ($result) $result .= ' | ';
                $completedLabel = $status === 'completed' ? 'Erledigt' : 'Abgebrochen';
                $result .= $completedLabel . ': ' . \Carbon\Carbon::parse($task->completed_at)->format('d.m.Y');
            } elseif ($task->updated_at) {
                if ($result) $result .= ' | ';
                $completedLabel = $status === 'completed' ? 'Erledigt' : 'Abgebrochen';
                $result .= $completedLabel . ': ' . \Carbon\Carbon::parse($task->updated_at)->format('d.m.Y');
            }
            
            return $result ?: '';
        }
        
        // Standard-Verhalten für alle anderen Aufgaben
        if (!$dueDate) {
            return '';
        }

        $now = now()->startOfDay();
        $due = \Carbon\Carbon::parse($dueDate)->startOfDay();
        $diffInDays = $now->diffInDays($due, false);

        if ($diffInDays < 0) {
            $days = abs($diffInDays);
            return $days == 1 ? 'Überfällig seit 1 Tag' : "Überfällig seit {$days} Tagen";
        } elseif ($diffInDays == 0) {
            return 'Heute fällig';
        } elseif ($diffInDays == 1) {
            return 'Morgen fällig';
        } elseif ($diffInDays <= 7) {
            return "Fällig in {$diffInDays} Tagen";
        } else {
            return $due->format('d.m.Y');
        }
    }

    private function logTaskChanges($task, $oldValues)
    {
        $fieldsToTrack = [
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'status' => 'Status',
            'priority' => 'Priorität',
            'due_date' => 'Fälligkeitsdatum',
            'task_type_id' => 'Aufgabentyp',
            'assigned_to' => 'Zugewiesen an',
            'owner_id' => 'Inhaber'
        ];

        foreach ($fieldsToTrack as $field => $label) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $task->$field;

            if ($oldValue != $newValue) {
                // Formatiere Werte für bessere Lesbarkeit
                $formattedOldValue = $this->formatHistoryValue($field, $oldValue);
                $formattedNewValue = $this->formatHistoryValue($field, $newValue);

                TaskHistory::logFieldChange(
                    $task,
                    auth()->id(),
                    $field,
                    $formattedOldValue,
                    $formattedNewValue
                );
            }
        }
    }

    private function formatHistoryValue($field, $value)
    {
        if ($value === null) {
            return 'Nicht gesetzt';
        }

        switch ($field) {
            case 'status':
                $statusLabels = [
                    'open' => 'Offen',
                    'in_progress' => 'In Bearbeitung',
                    'waiting_external' => 'Warte auf Extern',
                    'waiting_internal' => 'Warte auf Intern',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgebrochen'
                ];
                return $statusLabels[$value] ?? $value;

            case 'priority':
                $priorityLabels = [
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Dringend',
                    'blocker' => 'Blocker'
                ];
                return $priorityLabels[$value] ?? $value;

            case 'task_type_id':
                if ($value) {
                    $taskType = \App\Models\TaskType::find($value);
                    return $taskType ? $taskType->name : "ID: $value";
                }
                return 'Nicht gesetzt';

            case 'assigned_to':
            case 'owner_id':
                if ($value) {
                    $user = \App\Models\User::find($value);
                    return $user ? $user->name : "ID: $value";
                }
                return 'Nicht gesetzt';

            case 'due_date':
                if ($value) {
                    return \Carbon\Carbon::parse($value)->format('d.m.Y');
                }
                return 'Nicht gesetzt';

            default:
                return $value;
        }
    }

    /**
     * Sendet E-Mail-Benachrichtigungen für neue Aufgaben
     */
    private function sendNewTaskNotifications(Task $task): void
    {
        \Log::info('📧 Task: Sende Benachrichtigungen für neue Aufgabe', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'assigned_to' => $task->assigned_to,
            'owner_id' => $task->owner_id,
            'created_by' => auth()->id()
        ]);

        $usersToNotify = collect();
        
        // Zugewiesenen Benutzer hinzufügen
        if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser) {
                $usersToNotify->push($assignedUser);
            }
        }
        
        // Inhaber hinzufügen (falls unterschiedlich vom zugewiesenen Benutzer)
        if ($task->owner_id && $task->owner_id !== auth()->id() && $task->owner_id !== $task->assigned_to) {
            $ownerUser = User::find($task->owner_id);
            if ($ownerUser) {
                $usersToNotify->push($ownerUser);
            }
        }
        
        if ($usersToNotify->isEmpty()) {
            \Log::info('ℹ️ Task: Keine Benutzer für Aufgaben-Benachrichtigung', [
                'task_id' => $task->id
            ]);
            return;
        }
        
        $gmailService = app(\App\Services\GmailService::class);
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($usersToNotify as $user) {
            try {
                \Log::info('📧 Task: Sende Neue-Aufgabe E-Mail', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);

                // E-Mail-Template rendern
                $emailContent = view('emails.task-assignment', [
                    'user' => $user,
                    'task' => $task,
                    'author' => auth()->user(),
                    'isNewTask' => true,
                    'taskUrl' => route('filament.admin.resources.tasks.index')
                ])->render();

                $subject = "Neue Aufgabe zugewiesen - {$task->title}";

                $result = $gmailService->sendEmail(
                    $user->email,
                    $subject,
                    $emailContent
                );

                if ($result['success'] ?? false) {
                    \Log::info('✅ Task: Neue-Aufgabe E-Mail erfolgreich gesendet', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'message_id' => $result['message_id'] ?? null
                    ]);
                    $successCount++;
                } else {
                    \Log::error('❌ Task: Neue-Aufgabe E-Mail fehlgeschlagen', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'error' => $result['error'] ?? 'Unbekannter Fehler'
                    ]);
                    $errorCount++;
                }

            } catch (\Exception $e) {
                \Log::error('❌ Task: Fehler beim Senden der Neue-Aufgabe E-Mail', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }
        
        // Benachrichtigung an den Ersteller
        if ($successCount > 0) {
            Notification::make()
                ->title('Aufgaben-Benachrichtigungen versendet')
                ->body("E-Mail-Benachrichtigungen an {$successCount} Benutzer versendet")
                ->success()
                ->icon('heroicon-o-envelope')
                ->send();
        }
        
        if ($errorCount > 0) {
            Notification::make()
                ->title('E-Mail-Versand teilweise fehlgeschlagen')
                ->body("{$errorCount} E-Mails konnten nicht versendet werden")
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->send();
        }
    }
    
    /**
     * Sendet E-Mail-Benachrichtigungen für Aufgaben-Änderungen
     */
    private function sendTaskChangeNotifications(Task $task, array $oldValues): void
    {
        \Log::info('📧 Task: Prüfe Änderungen für E-Mail-Benachrichtigungen', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'old_assigned_to' => $oldValues['assigned_to'] ?? null,
            'new_assigned_to' => $task->assigned_to,
            'old_owner_id' => $oldValues['owner_id'] ?? null,
            'new_owner_id' => $task->owner_id,
            'changed_by' => auth()->id()
        ]);
        
        $usersToNotify = collect();
        $changes = [];
        
        // Prüfe relevante Änderungen
        $fieldsToCheck = [
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'status' => 'Status',
            'priority' => 'Priorität',
            'due_date' => 'Fälligkeitsdatum',
            'assigned_to' => 'Zugewiesen an',
            'owner_id' => 'Inhaber'
        ];
        
        foreach ($fieldsToCheck as $field => $label) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $task->$field;
            
            if ($oldValue != $newValue) {
                $changes[$label] = [
                    'old_value' => $this->formatHistoryValue($field, $oldValue),
                    'new_value' => $this->formatHistoryValue($field, $newValue)
                ];
            }
        }
        
        // Keine relevanten Änderungen
        if (empty($changes)) {
            \Log::info('ℹ️ Task: Keine relevanten Änderungen für E-Mail-Benachrichtigung', [
                'task_id' => $task->id
            ]);
            return;
        }
        
        // Neue Zuweisung - E-Mail an neuen Benutzer
        if (isset($changes['Zugewiesen an']) && $task->assigned_to && $task->assigned_to !== auth()->id()) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser) {
                $usersToNotify->push($assignedUser);
            }
        }
        
        // Neuer Inhaber - E-Mail an neuen Inhaber
        if (isset($changes['Inhaber']) && $task->owner_id && $task->owner_id !== auth()->id() && $task->owner_id !== $task->assigned_to) {
            $ownerUser = User::find($task->owner_id);
            if ($ownerUser) {
                $usersToNotify->push($ownerUser);
            }
        }
        
        // Bestehende Zuweisungen - E-Mail bei anderen wichtigen Änderungen
        if (!isset($changes['Zugewiesen an']) && !isset($changes['Inhaber'])) {
            // E-Mail an aktuell zugewiesenen Benutzer
            if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
                $assignedUser = User::find($task->assigned_to);
                if ($assignedUser) {
                    $usersToNotify->push($assignedUser);
                }
            }
            
            // E-Mail an aktuellen Inhaber
            if ($task->owner_id && $task->owner_id !== auth()->id() && $task->owner_id !== $task->assigned_to) {
                $ownerUser = User::find($task->owner_id);
                if ($ownerUser) {
                    $usersToNotify->push($ownerUser);
                }
            }
        }
        
        if ($usersToNotify->isEmpty()) {
            \Log::info('ℹ️ Task: Keine Benutzer für Änderungs-Benachrichtigung', [
                'task_id' => $task->id,
                'changes' => array_keys($changes)
            ]);
            return;
        }
        
        $gmailService = app(\App\Services\GmailService::class);
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($usersToNotify as $user) {
            try {
                \Log::info('📧 Task: Sende Änderungs E-Mail', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'changes' => array_keys($changes)
                ]);

                // E-Mail-Template rendern
                $emailContent = view('emails.task-assignment', [
                    'user' => $user,
                    'task' => $task,
                    'author' => auth()->user(),
                    'isNewTask' => false,
                    'changes' => $changes,
                    'taskUrl' => route('filament.admin.resources.tasks.index')
                ])->render();

                $subject = "Aufgabe geändert - {$task->title}";

                $result = $gmailService->sendEmail(
                    $user->email,
                    $subject,
                    $emailContent
                );

                if ($result['success'] ?? false) {
                    \Log::info('✅ Task: Änderungs E-Mail erfolgreich gesendet', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'message_id' => $result['message_id'] ?? null
                    ]);
                    $successCount++;
                } else {
                    \Log::error('❌ Task: Änderungs E-Mail fehlgeschlagen', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'error' => $result['error'] ?? 'Unbekannter Fehler'
                    ]);
                    $errorCount++;
                }

            } catch (\Exception $e) {
                \Log::error('❌ Task: Fehler beim Senden der Änderungs E-Mail', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }
        
        // Benachrichtigung an den Bearbeiter
        if ($successCount > 0) {
            Notification::make()
                ->title('Änderungs-Benachrichtigungen versendet')
                ->body("E-Mail-Benachrichtigungen an {$successCount} Benutzer versendet")
                ->success()
                ->icon('heroicon-o-envelope')
                ->send();
        }
        
        if ($errorCount > 0) {
            Notification::make()
                ->title('E-Mail-Versand teilweise fehlgeschlagen')
                ->body("{$errorCount} E-Mails konnten nicht versendet werden")
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->send();
        }
    }
    
    /**
     * Sendet E-Mail-Benachrichtigung bei Status-Änderung durch Drag & Drop
     */
    private function sendStatusChangeNotification(Task $task, string $oldStatus, string $newStatus): void
    {
        // Nur senden wenn sich der Status tatsächlich geändert hat
        if ($oldStatus === $newStatus) {
            return;
        }
        
        // Sammle alle Benutzer, die benachrichtigt werden sollen
        $usersToNotify = collect();
        
        // Inhaber hinzufügen (falls nicht der Veränderer)
        if ($task->owner_id && $task->owner_id !== auth()->id()) {
            $owner = User::find($task->owner_id);
            if ($owner) {
                $usersToNotify->push($owner);
            }
        }
        
        // Zugewiesenen Benutzer hinzufügen (falls nicht der Veränderer und nicht bereits hinzugefügt)
        if ($task->assigned_to && $task->assigned_to !== auth()->id() && $task->assigned_to !== $task->owner_id) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser) {
                $usersToNotify->push($assignedUser);
            }
        }
        
        if ($usersToNotify->isEmpty()) {
            \Log::info('ℹ️ Task: Keine Status-Änderungs-Benachrichtigung - keine relevanten Benutzer', [
                'task_id' => $task->id,
                'owner_id' => $task->owner_id,
                'assigned_to' => $task->assigned_to,
                'changed_by' => auth()->id()
            ]);
            return;
        }
        
        // Status-Labels für die E-Mail
        $statusLabels = [
            'open' => 'Offen',
            'in_progress' => 'In Bearbeitung',
            'waiting_external' => 'Warte auf Extern',
            'waiting_internal' => 'Warte auf Intern',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgebrochen'
        ];
        
        $gmailService = app(\App\Services\GmailService::class);
        $successCount = 0;
        $errorCount = 0;
        $notifiedUsers = [];
        
        foreach ($usersToNotify as $user) {
            try {
                \Log::info('📧 Task: Sende Status-Änderungs-Benachrichtigung', [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => auth()->id()
                ]);

                // E-Mail-Adresse validieren
                if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    \Log::warning('❌ Task: Ungültige E-Mail-Adresse für Status-Änderungs-Benachrichtigung', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'email' => $user->email
                    ]);
                    $errorCount++;
                    continue;
                }
                
                // E-Mail-Inhalt rendern
                $emailContent = view('emails.task-status-change', [
                    'user' => $user,
                    'task' => $task,
                    'author' => auth()->user(),
                    'oldStatus' => $oldStatus,
                    'newStatus' => $newStatus,
                    'oldStatusLabel' => $statusLabels[$oldStatus] ?? $oldStatus,
                    'newStatusLabel' => $statusLabels[$newStatus] ?? $newStatus,
                    'changeDate' => now(),
                    'taskUrl' => route('filament.admin.resources.tasks.index')
                ])->render();

                $subject = "Aufgaben-Status geändert - {$task->title}";

                // E-Mail über Gmail Service senden
                $result = $gmailService->sendEmail(
                    $user->email,
                    $subject,
                    $emailContent
                );

                if ($result['success'] ?? false) {
                    \Log::info('✅ Task: Status-Änderungs-Benachrichtigung erfolgreich gesendet', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_name' => $user->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'subject' => $subject,
                        'message_id' => $result['message_id'] ?? null
                    ]);
                    $successCount++;
                    $notifiedUsers[] = $user->name;
                } else {
                    \Log::error('❌ Task: Status-Änderungs-Benachrichtigung fehlgeschlagen', [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_name' => $user->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'error' => $result['error'] ?? 'Unbekannter Fehler'
                    ]);
                    $errorCount++;
                }

            } catch (\Exception $e) {
                \Log::error('❌ Task: Fehler beim Senden der Status-Änderungs-Benachrichtigung', [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
        }
        
        // Benachrichtigungen an den Veränderer senden
        if ($successCount > 0) {
            $userList = implode(', ', $notifiedUsers);
            $message = $successCount === 1 
                ? "Status-Änderungs-Benachrichtigung an {$userList} versendet"
                : "Status-Änderungs-Benachrichtigungen an {$successCount} Benutzer versendet ({$userList})";
            
            Notification::make()
                ->title('Status-Änderungs-Benachrichtigungen versendet')
                ->body($message)
                ->success()
                ->icon('heroicon-o-envelope')
                ->send();
        }
        
        if ($errorCount > 0) {
            $message = $errorCount === 1 
                ? 'Status-Änderungs-Benachrichtigung konnte nicht versendet werden'
                : "{$errorCount} Status-Änderungs-Benachrichtigungen konnten nicht versendet werden";
            
            Notification::make()
                ->title('E-Mail-Versand fehlgeschlagen')
                ->body($message)
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->send();
        }
    }

    // Task sorting within columns via drag & drop
    public function updateTaskOrder($taskId, $newStatus, $fromStatus, $orderedIds = [])
    {
        $task = Task::find($taskId);
        if (!$task) return;

        $oldStatus = $task->status;
        
        // Spezielle Behandlung für "recurring" Spalte
        if ($newStatus === 'recurring') {
            // Setze is_recurring auf true, behalte aber den eigentlichen Status
            $task->is_recurring = true;
            // Wenn die Task noch keinen Status hat oder von recurring kommt, setze auf 'open'
            if (!$task->status || $task->status === 'recurring') {
                $task->status = 'open';
            }
            
            // Log recurring change
            TaskHistory::logFieldChange($task, auth()->id(), 'is_recurring', $task->getOriginal('is_recurring') ? 'Ja' : 'Nein', 'Ja');
            
        } else {
            // Normale Spalten - setze is_recurring auf false und aktualisiere Status
            $wasRecurring = $task->is_recurring;
            $task->is_recurring = false;
            
            // Update task status if changed
            if ($newStatus !== $oldStatus) {
                $task->status = $newStatus;
                
                // Set completed_at if status changed to completed
                if ($newStatus === 'completed' && $task->completed_at === null) {
                    $task->completed_at = now();
                } elseif ($newStatus !== 'completed') {
                    $task->completed_at = null;
                }
                
                // Log status change
                TaskHistory::logFieldChange($task, auth()->id(), 'status', $oldStatus, $newStatus);
            }
            
            // Log recurring change wenn von recurring weg verschoben
            if ($wasRecurring) {
                TaskHistory::logFieldChange($task, auth()->id(), 'is_recurring', 'Ja', 'Nein');
            }
            
            // Send status change notification to task owner
            if ($newStatus !== $oldStatus || $wasRecurring) {
                $this->sendStatusChangeNotification($task, $oldStatus, $newStatus);
            }
        }

        // Update sort order for all tasks in the target column (exclude blockers from manual sorting)
        $this->updateTaskOrderFromDrop($newStatus, $orderedIds);

        $task->save();

        // Refresh the page to show updated order
        $this->dispatch('task-updated');
    }

    /**
     * Aktualisiert die Reihenfolge der Aufgaben nach einem Drag & Drop
     */
    private function updateTaskOrderFromDrop($status, $orderedIds = [])
    {
        if (empty($orderedIds)) {
            return;
        }

        // Nur nicht-Blocker Tasks können manuell sortiert werden
        $nonBlockerIds = [];
        foreach ($orderedIds as $id) {
            if ($id) {
                $checkTask = Task::find($id);
                if ($checkTask && !$checkTask->isBlocker()) {
                    $nonBlockerIds[] = $id;
                }
            }
        }

        // Reihenfolge aktualisieren
        foreach ($nonBlockerIds as $index => $id) {
            Task::where('id', $id)
                ->where('status', $status)
                ->update(['sort_order' => $index + 1]);
        }
    }
}
