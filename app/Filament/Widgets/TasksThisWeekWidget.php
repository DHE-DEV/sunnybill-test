<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TasksThisWeekWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        
        // Aufgaben die diese Woche fällig sind
        $dueThisWeekQuery = Task::whereBetween('due_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);
        $dueThisWeek = $dueThisWeekQuery->count();
        $dueThisWeekCompleted = (clone $dueThisWeekQuery)->where('status', 'completed')->count();
        $dueThisWeekOpen = (clone $dueThisWeekQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Diese Woche erstellte Aufgaben
        $createdThisWeek = Task::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        
        // Diese Woche abgeschlossene Aufgaben
        $completedThisWeek = Task::whereBetween('completed_at', [$startOfWeek, $endOfWeek])->count();
        
        // Wartende Aufgaben
        $waitingTasks = Task::whereIn('status', ['waiting_external', 'waiting_internal'])->count();
        
        // Überfällige Aufgaben (älter als diese Woche)
        $overdueTasks = Task::where('due_date', '<', $startOfWeek->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        
        // Fortschritt dieser Woche (Prozent abgeschlossener Aufgaben)
        $weekProgress = $dueThisWeek > 0 ? round(($dueThisWeekCompleted / $dueThisWeek) * 100, 1) : 0;
        
        return [
            Stat::make('Diese Woche fällig', $dueThisWeek)
                ->description($dueThisWeekCompleted . ' abgeschlossen, ' . $dueThisWeekOpen . ' offen')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($dueThisWeekOpen > 0 ? 'warning' : 'success'),
                
            Stat::make('Wochenfortschritt', $weekProgress . '%')
                ->description('Abgeschlossene Aufgaben')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($weekProgress >= 80 ? 'success' : ($weekProgress >= 50 ? 'warning' : 'danger')),
                
            Stat::make('Neue Aufgaben', $createdThisWeek)
                ->description('Diese Woche erstellt')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
                
            Stat::make('Abgeschlossen', $completedThisWeek)
                ->description('Diese Woche erledigt')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Wartende Aufgaben', $waitingTasks)
                ->description('Extern & Intern')
                ->descriptionIcon('heroicon-m-clock')
                ->color($waitingTasks > 0 ? 'info' : 'success'),
                
            Stat::make('Überfällig', $overdueTasks)
                ->description('Vor dieser Woche fällig')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),
        ];
    }
}