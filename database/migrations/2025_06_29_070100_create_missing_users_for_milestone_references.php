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
        // Sammle alle User-IDs aus SolarPlantMilestones
        $userIds = collect();
        
        // User-IDs aus SolarPlantMilestones sammeln
        $milestoneUserIds = DB::table('solar_plant_milestones')
            ->select('project_manager_id', 'last_responsible_user_id')
            ->whereNotNull('project_manager_id')
            ->orWhereNotNull('last_responsible_user_id')
            ->get();
            
        foreach ($milestoneUserIds as $milestone) {
            if ($milestone->project_manager_id) {
                $userIds->push($milestone->project_manager_id);
            }
            if ($milestone->last_responsible_user_id) {
                $userIds->push($milestone->last_responsible_user_id);
            }
        }
        
        // Eindeutige User-IDs
        $uniqueUserIds = $userIds->unique()->filter();
        
        if ($uniqueUserIds->isEmpty()) {
            echo "No user references found in solar_plant_milestones table.\n";
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
            
            echo "Created missing user with ID: {$userId} for milestone references\n";
        }
        
        if ($missingUserIds->isEmpty()) {
            echo "No missing users found for milestone references. All referenced users already exist.\n";
        } else {
            echo "Created " . $missingUserIds->count() . " missing users for milestone references.\n";
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