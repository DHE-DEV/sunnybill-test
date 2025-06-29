<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TasksThisMonthWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '120s';
    
    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Aufgaben die diesen Monat fällig sind
        $dueThisMonthQuery = Task::whereBetween('due_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
        $dueThisMonth = $dueThisMonthQuery->count();
        $dueThisMonthCompleted = (clone $dueThisMonthQuery)->where('status', 'completed')->count();
        $dueThisMonthOpen = (clone $dueThisMonthQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Diesen Monat erstellte Aufgaben
        $createdThisMonth = Task::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        
        // Diesen Monat abgeschlossene Aufgaben
        $completedThisMonth = Task::whereBetween('completed_at', [$startOfMonth, $endOfMonth])->count();
        
        // Aufgaben nach Priorität (diesen Monat fällig)
        $urgentTasks = (clone $dueThisMonthQuery)->where('priority', 'urgent')->whereNotIn('status', ['completed', 'cancelled'])->count();
        $highPriorityTasks = (clone $dueThisMonthQuery)->where('priority', 'high')->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Aufgaben nach Status
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        $waitingTasks = Task::whereIn('status', ['waiting_external', 'waiting_internal'])->count();
        
        // Monatsfortschritt
        $monthProgress = $dueThisMonth > 0 ? round(($dueThisMonthCompleted / $dueThisMonth) * 100, 1) : 0;
        
        // Durchschnittliche Bearbeitungszeit (in Tagen) für abgeschlossene Aufgaben diesen Monat
        $completedTasksThisMonth = Task::whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->whereNotNull('created_at')
            ->get();
        
        $avgProcessingDays = 0;
        if ($completedTasksThisMonth->count() > 0) {
            $totalDays = $completedTasksThisMonth->sum(function ($task) {
                return $task->completed_at->diffInDays($task->created_at);
            });
            $avgProcessingDays = round($totalDays / $completedTasksThisMonth->count(), 1);
        }
        
        return [
            Stat::make('Diesen Monat fällig', $dueThisMonth)
                ->description($dueThisMonthCompleted . ' abgeschlossen, ' . $dueThisMonthOpen . ' offen')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($dueThisMonthOpen > 0 ? 'warning' : 'success'),
                
            Stat::make('Monatsfortschritt', $monthProgress . '%')
                ->description('Abgeschlossene Aufgaben')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($monthProgress >= 80 ? 'success' : ($monthProgress >= 50 ? 'warning' : 'danger')),
                
            Stat::make('Neue Aufgaben', $createdThisMonth)
                ->description('Diesen Monat erstellt')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
                
            Stat::make('Abgeschlossen', $completedThisMonth)
                ->description('Ø ' . $avgProcessingDays . ' Tage Bearbeitung')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Dringende Aufgaben', $urgentTasks + $highPriorityTasks)
                ->description($urgentTasks . ' dringend, ' . $highPriorityTasks . ' hoch')
                ->descriptionIcon('heroicon-m-fire')
                ->color(($urgentTasks + $highPriorityTasks) > 0 ? 'danger' : 'success'),
                
            Stat::make('Aktive Aufgaben', $inProgressTasks + $waitingTasks)
                ->description($inProgressTasks . ' in Bearbeitung, ' . $waitingTasks . ' wartend')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('primary'),
        ];
    }
}