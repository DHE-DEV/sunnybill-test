<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FilteredTasksStatsWidget extends BaseWidget
{
    public ?string $timeFilter = 'today';
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $dateRange = $this->getDateRange();
        
        // Aufgaben im gewählten Zeitraum
        $tasksInPeriod = Task::whereBetween('due_date', $dateRange)->count();
        $tasksCompleted = Task::whereBetween('due_date', $dateRange)->where('status', 'completed')->count();
        $tasksOpen = Task::whereBetween('due_date', $dateRange)->whereNotIn('status', ['completed', 'cancelled'])->count();
        $tasksOverdue = Task::where('due_date', '<', now()->toDateString())->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Aufgaben nach Priorität im Zeitraum
        $urgentTasks = Task::whereBetween('due_date', $dateRange)
            ->where('priority', 'urgent')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
            
        $highPriorityTasks = Task::whereBetween('due_date', $dateRange)
            ->where('priority', 'high')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        
        // Aufgaben nach Status
        $inProgressTasks = Task::whereBetween('due_date', $dateRange)->where('status', 'in_progress')->count();
        $waitingTasks = Task::whereBetween('due_date', $dateRange)->whereIn('status', ['waiting_external', 'waiting_internal'])->count();
        
        // Fortschritt berechnen
        $progress = $tasksInPeriod > 0 ? round(($tasksCompleted / $tasksInPeriod) * 100, 1) : 0;
        
        $periodLabel = $this->getPeriodLabel();
        
        return [
            Stat::make("Aufgaben {$periodLabel}", $tasksInPeriod)
                ->description("{$tasksCompleted} abgeschlossen, {$tasksOpen} offen")
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($tasksOpen > 0 ? 'warning' : 'success'),
                
            Stat::make('Fortschritt', $progress . '%')
                ->description('Abgeschlossene Aufgaben')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($progress >= 80 ? 'success' : ($progress >= 50 ? 'warning' : 'danger')),
                
            Stat::make('Hohe Priorität', $urgentTasks + $highPriorityTasks)
                ->description("{$urgentTasks} dringend, {$highPriorityTasks} hoch")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(($urgentTasks + $highPriorityTasks) > 0 ? 'danger' : 'success'),
                
            Stat::make('In Bearbeitung', $inProgressTasks)
                ->description("{$waitingTasks} wartend")
                ->descriptionIcon('heroicon-m-play')
                ->color('primary'),
                
            Stat::make('Überfällig', $tasksOverdue)
                ->description('Vor heute fällig')
                ->descriptionIcon('heroicon-m-clock')
                ->color($tasksOverdue > 0 ? 'danger' : 'success'),
        ];
    }
    
    protected function getDateRange(): array
    {
        return match($this->timeFilter) {
            'today' => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ],
            'next_7_days' => [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(7)->toDateString()
            ],
            'next_30_days' => [
                Carbon::today()->toDateString(),
                Carbon::today()->addDays(30)->toDateString()
            ],
            default => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ]
        };
    }
    
    protected function getPeriodLabel(): string
    {
        return match($this->timeFilter) {
            'today' => 'heute',
            'next_7_days' => 'nächste 7 Tage',
            'next_30_days' => 'nächste 30 Tage',
            default => 'heute'
        };
    }
}