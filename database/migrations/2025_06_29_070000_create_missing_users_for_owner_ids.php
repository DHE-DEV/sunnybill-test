<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sammle alle User-IDs aus der owner_id Spalte
        $userIds = collect();
        
        // User-IDs aus Tasks sammeln (owner_id Spalte)
        $taskOwnerIds = DB::table('tasks')
            ->select('owner_id')
            ->whereNotNull('owner_id')
            ->get();
            
        foreach ($taskOwnerIds as $task) {
            if ($task->owner_id) {
                $userIds->push($task->owner_id);
            }
        }
        
        // Eindeutige User-IDs
        $uniqueUserIds = $userIds->unique()->filter();
        
        if ($uniqueUserIds->isEmpty()) {
            echo "No owner_id references found in tasks table.\n";
            return;
        }
        
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
            
            echo "Created missing user with ID: {$userId} for owner_id references\n";
        }
        
        if ($missingUserIds->isEmpty()) {
            echo "No missing users found for owner_id references. All referenced users already exist.\n";
        } else {
            echo "Created " . $missingUserIds->count() . " missing users for owner_id references.\n";
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