<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Task;
use App\Models\SolarPlantMilestone;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sammle alle User-IDs aus Tasks und SolarPlantMilestones
        $userIds = collect();
        
        // User-IDs aus Tasks sammeln (nur existierende Spalten)
        $taskUserIds = DB::table('tasks')
            ->select('assigned_to', 'created_by')
            ->whereNotNull('assigned_to')
            ->orWhereNotNull('created_by')
            ->get();
            
        foreach ($taskUserIds as $task) {
            if ($task->assigned_to) {
                $userIds->push($task->assigned_to);
            }
            if ($task->created_by) {
                $userIds->push($task->created_by);
            }
        }
        
        // User-IDs aus SolarPlantMilestones sammeln werden später in einer separaten Migration behandelt
        // da die entsprechenden Spalten noch nicht existieren
        
        // Eindeutige User-IDs
        $uniqueUserIds = $userIds->unique()->filter();
        
        // Prüfe welche User bereits existieren
        $existingUserIds = User::whereIn('id', $uniqueUserIds)->pluck('id');
        $missingUserIds = $uniqueUserIds->diff($existingUserIds);
        
        // Erstelle fehlende User
        foreach ($missingUserIds as $userId) {
            User::create([
                'id' => $userId,
                'name' => "User {$userId}",
                'email' => "user{$userId}@example.com",
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "Created missing user with ID: {$userId}\n";
        }
        
        if ($missingUserIds->isEmpty()) {
            echo "No missing users found. All referenced users already exist.\n";
        } else {
            echo "Created " . $missingUserIds->count() . " missing users.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Diese Migration kann nicht rückgängig gemacht werden,
        // da wir nicht wissen, welche User vor der Migration existierten
        echo "This migration cannot be reversed as we don't know which users existed before.\n";
    }
};
