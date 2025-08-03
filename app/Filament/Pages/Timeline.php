<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Task;
use App\Models\SolarPlantMilestone;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Timeline extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.timeline';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Timeline';

    protected static ?string $navigationLabel = 'Timeline';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public function getTimelineData(): Collection
    {
        // Sammle Aufgaben und Projekttermine
        $tasks = Task::with(['taskType', 'assignedUser', 'owner', 'customer', 'solarPlant'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'type' => 'task',
                    'title' => $task->title ?? 'Unbenannte Aufgabe',
                    'description' => $task->description ? strip_tags($task->description) : null,
                    'date' => $task->due_date,
                    'time' => $task->due_time,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assigned_user' => $task->assignedUser?->name,
                    'owner' => $task->owner?->name,
                    'customer' => $task->customer?->company_name,
                    'project' => $task->solarPlant?->name, // Tasks können ein Projekt haben
                    'task_type' => $task->taskType?->name ?? 'Allgemein',
                    'task_type_color' => $task->taskType?->color ?? 'gray',
                    'is_overdue' => $task->due_date ? Carbon::parse($task->due_date)->isPast() : false,
                    'is_due_today' => $task->due_date ? Carbon::parse($task->due_date)->isToday() : false,
                    'url' => route('filament.admin.resources.tasks.view', $task),
                    'actions' => [
                        'view' => route('filament.admin.resources.tasks.view', $task),
                        'edit' => route('filament.admin.resources.tasks.edit', $task),
                        'can_complete' => $task->status !== 'completed',
                        'can_start' => $task->status === 'open',
                    ],
                ];
            });

        $milestones = SolarPlantMilestone::with(['solarPlant', 'projectManager', 'lastResponsibleUser'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('planned_date')
            ->get()
            ->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'type' => 'milestone',
                    'title' => $milestone->title ?? 'Unbenannter Meilenstein',
                    'description' => $milestone->description ? strip_tags($milestone->description) : null,
                    'date' => $milestone->planned_date,
                    'time' => null,
                    'status' => $milestone->status,
                    'priority' => null,
                    'assigned_user' => $milestone->lastResponsibleUser?->name,
                    'owner' => $milestone->projectManager?->name,
                    'customer' => null,
                    'project' => $milestone->solarPlant?->name,
                    'is_overdue' => $milestone->planned_date ? Carbon::parse($milestone->planned_date)->isPast() : false,
                    'is_due_today' => $milestone->planned_date ? Carbon::parse($milestone->planned_date)->isToday() : false,
                    'url' => $milestone->solarPlant ? route('filament.admin.resources.solar-plants.view', $milestone->solarPlant) : '#',
                    'actions' => [
                        'view' => $milestone->solarPlant ? route('filament.admin.resources.solar-plants.view', $milestone->solarPlant) : '#',
                        'edit' => $milestone->solarPlant ? route('filament.admin.resources.solar-plants.edit', $milestone->solarPlant) : '#',
                        'can_complete' => $milestone->status !== 'completed',
                        'can_start' => $milestone->status === 'planned',
                    ],
                ];
            });

        // Kombiniere und sortiere nach Datum
        return $tasks->concat($milestones)
            ->sortBy('date')
            ->groupBy(function ($item) {
                return Carbon::parse($item['date'])->format('Y-m-d');
            });
    }
}
