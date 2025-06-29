<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TasksTodayWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        
        // Aufgaben die heute fällig sind
        $dueTodayQuery = Task::whereDate('due_date', $today);
        $dueToday = $dueTodayQuery->count();
        $dueTodayCompleted = (clone $dueTodayQuery)->where('status', 'completed')->count();
        $dueTodayOverdue = (clone $dueTodayQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();
        
        // Heute erstellte Aufgaben
        $createdToday = Task::whereDate('created_at', $today)->count();
        
        // Heute abgeschlossene Aufgaben
        $completedToday = Task::whereDate('completed_at', $today)->count();
        
        // Offene Aufgaben mit hoher Priorität
        $highPriorityOpen = Task::whereIn('priority', ['high', 'urgent'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
        
        // In Bearbeitung heute
        $inProgressToday = Task::where('status', 'in_progress')
            ->whereDate('updated_at', $today)
            ->count();
        
        return [
            Stat::make('Heute fällig', $dueToday)
                ->description($dueTodayCompleted . ' abgeschlossen, ' . $dueTodayOverdue . ' offen')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($dueTodayOverdue > 0 ? 'danger' : 'success'),
                
            Stat::make('Heute erstellt', $createdToday)
                ->description('Neue Aufgaben')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
                
            Stat::make('Heute abgeschlossen', $completedToday)
                ->description('Erledigte Aufgaben')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Hohe Priorität', $highPriorityOpen)
                ->description('Dringend & Hoch (offen)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($highPriorityOpen > 0 ? 'warning' : 'success'),
                
            Stat::make('In Bearbeitung', $inProgressToday)
                ->description('Heute bearbeitet')
                ->descriptionIcon('heroicon-m-play')
                ->color('primary'),
        ];
    }
}