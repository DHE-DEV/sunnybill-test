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
    public ?int $editTaskTypeId = null;
    public ?int $editAssignedTo = null;
    public ?int $editOwnerId = null;
    
    // Notes modal properties
    public bool $showNotesModal = false;
    public ?Task $notesTask = null;
    public string $newNoteContent = '';
    
    // History modal properties
    public bool $showHistoryModal = false;
    public ?Task $historyTask = null;

    public function mount(): void
    {
        parent::mount();
        // Prüfe URL-Parameter oder Session für den aktuellen Zustand
        $this->showStatistics = request()->get('statistics', false);
        $this->showBoard = request()->get('board', true); // Standardmäßig Board anzeigen
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

            // Optional: Logik zur Neuordnung basierend auf $orderedIds
            // ...
        }

        // Neu rendern, um die Änderungen zu übernehmen (optional, aber oft nützlich)
        $this->resetPage();
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
            $this->editingTask->status = $this->editStatus;
            $this->editingTask->priority = $this->editPriority;
            $this->editingTask->due_date = $this->editDueDate ? \Carbon\Carbon::parse($this->editDueDate) : null;
            $this->editingTask->task_type_id = $this->editTaskTypeId;
            $this->editingTask->assigned_to = $this->editAssignedTo;
            $this->editingTask->owner_id = $this->editOwnerId;
            
            $this->editingTask->save();
            
            // Historie für geänderte Felder erstellen
            $this->logTaskChanges($this->editingTask, $oldValues);
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
    }

    public function createTask()
    {
        // Alle anderen Aufgaben im gleichen Status um 1 nach unten verschieben
        \App\Models\Task::where('status', $this->editStatus)
            ->increment('sort_order');

        // Neue Aufgabe erstellen
        $task = \App\Models\Task::create([
            'title' => $this->editTitle,
            'description' => $this->editDescription,
            'status' => $this->editStatus,
            'priority' => $this->editPriority,
            'due_date' => $this->editDueDate ? \Carbon\Carbon::parse($this->editDueDate) : null,
            'task_type_id' => $this->editTaskTypeId ?: null,
            'assigned_to' => $this->editAssignedTo ?: null,
            'owner_id' => $this->editOwnerId ?: null,
            'created_by' => auth()->id(),
            'sort_order' => 1, // Neue Aufgaben immer ganz oben
        ]);

        // Historie für Task-Erstellung
        TaskHistory::logTaskCreation($task, auth()->id());

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
        $mentionedUsers = [];
        
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
    }
    
    /**
     * Extrahiert @mentions aus dem Text (maximal 3 Wörter pro Mention)
     */
    private function extractMentions(string $content): array
    {
        // Verbesserte Regex: maximal 3 Wörter nach @, dann Stopp bei Leerzeichen oder Nicht-Buchstaben
        preg_match_all('/@([a-zA-ZäöüÄÖÜß]+(?:\s+[a-zA-ZäöüÄÖÜß]+){0,2})(?=\s+|$|[^a-zA-ZäöüÄÖÜß\s])/u', $content, $matches);
        return array_map('trim', $matches[1]);
    }
    
    /**
     * Debug-Funktion für @mentions-Extraktion
     */
    private function debugExtractMentions(string $content): array
    {
        $pattern = '/@([a-zA-ZäöüÄÖÜß]+(?:\s+[a-zA-ZäöüÄÖÜß]+){0,2})(?=\s+|$|[^a-zA-ZäöüÄÖÜß\s])/u';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        return [
            'full_matches' => $matches,
            'pattern_explanation' => 'Regex erfasst maximal 3 Wörter nach @, dann Stopp bei Leerzeichen oder Nicht-Buchstaben',
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
    public function getDueDateText($dueDate)
    {
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

    // Task sorting within columns via drag & drop
    public function updateTaskOrder($taskId, $newStatus, $orderedIds)
    {
        $task = Task::find($taskId);
        if (!$task) return;

        $oldStatus = $task->status;
        
        // Update task status if changed (Blocker-Tasks können zwischen Spalten verschoben werden)
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

        // Update sort order for all tasks in the target column (exclude blockers from manual sorting)
        $nonBlockerIds = [];
        foreach ($orderedIds as $id) {
            if ($id) {
                $checkTask = Task::find($id);
                if ($checkTask && !$checkTask->isBlocker()) {
                    $nonBlockerIds[] = $id;
                }
            }
        }

        // Nur nicht-Blocker Tasks können manuell sortiert werden
        if (!$task->isBlocker()) {
            foreach ($nonBlockerIds as $index => $id) {
                Task::where('id', $id)
                    ->where('status', $newStatus)
                    ->update(['sort_order' => $index + 1]);
            }
            
            // Log position change if within same status
            if ($newStatus === $oldStatus && count($nonBlockerIds) > 1) {
                TaskHistory::logFieldChange($task, auth()->id(), 'position', 'Position geändert', 'Neue Reihenfolge in Spalte');
            }
        }

        $task->save();

        // Refresh the page to show updated order
        $this->dispatch('task-updated');
    }
}
