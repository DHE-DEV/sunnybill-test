<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Erweitere die Status-Enum um 'waiting' und 'recurring'
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'cancelled', 'waiting', 'recurring') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Setze alle 'waiting' und 'recurring' Status zurück auf 'open'
        DB::statement("UPDATE tasks SET status = 'open' WHERE status IN ('waiting', 'recurring')");
        
        // Entferne die neuen Status-Werte aus der Enum
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open'");
    }
};
