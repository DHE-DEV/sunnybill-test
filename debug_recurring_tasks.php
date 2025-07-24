<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Laravel App bootstrappen
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Analysiere wiederkehrende Aufgaben...\n";

// Hole alle wiederkehrenden Aufgaben
$recurringTasks = DB::table('tasks')
    ->where('is_recurring', true)
    ->whereNull('deleted_at')
    ->get();

echo "📊 Gefundene wiederkehrende Aufgaben: " . $recurringTasks->count() . "\n\n";

if ($recurringTasks->count() > 0) {
    echo "🔍 Details der wiederkehrenden Aufgaben:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($recurringTasks as $task) {
        echo "ID: {$task->id}\n";
        echo "Titel: {$task->title}\n";
        echo "Status: {$task->status}\n";
        echo "Ist wiederkehrend: " . ($task->is_recurring ? 'Ja' : 'Nein') . "\n";
        echo "Wiederholungsmuster: " . ($task->recurring_pattern ?? 'Nicht definiert') . "\n";
        echo "Fälligkeitsdatum: " . ($task->due_date ?? 'Nicht gesetzt') . "\n";
        echo "Erstellt am: {$task->created_at}\n";
        echo "Aktualisiert am: {$task->updated_at}\n";
        
        if ($task->completed_at) {
            echo "Abgeschlossen am: {$task->completed_at}\n";
        }
        
        echo str_repeat("-", 40) . "\n";
    }
    
    // Analysiere Status-Verteilung
    $statusCounts = DB::table('tasks')
        ->where('is_recurring', true)
        ->whereNull('deleted_at')
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->get();
    
    echo "\n📈 Status-Verteilung wiederkehrender Aufgaben:\n";
    foreach ($statusCounts as $statusCount) {
        echo "- {$statusCount->status}: {$statusCount->count}\n";
    }
    
    // Prüfe auf abgeschlossene wiederkehrende Aufgaben
    $completedRecurring = DB::table('tasks')
        ->where('is_recurring', true)
        ->where('status', 'completed')
        ->whereNull('deleted_at')
        ->count();
    
    echo "\n🎯 Abgeschlossene wiederkehrende Aufgaben: {$completedRecurring}\n";
    
    if ($completedRecurring > 0) {
        echo "⚠️  Problem: Abgeschlossene wiederkehrende Aufgaben sollten neue Instanzen erstellen!\n";
    }
    
    // Prüfe auf in_progress wiederkehrende Aufgaben
    $inProgressRecurring = DB::table('tasks')
        ->where('is_recurring', true)
        ->where('status', 'in_progress')
        ->whereNull('deleted_at')
        ->count();
    
    echo "🔄 Wiederkehrende Aufgaben mit 'in_progress' Status: {$inProgressRecurring}\n";
    
    if ($inProgressRecurring > 0) {
        echo "❓ Diese sollten überprüft werden - sind sie wirklich in Bearbeitung oder sollten sie erneuert werden?\n";
    }
    
} else {
    echo "ℹ️  Keine wiederkehrenden Aufgaben gefunden.\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Analyse abgeschlossen.\n";
