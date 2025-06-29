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
        // Für MySQL müssen wir die Enum-Spalte neu definieren
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Zurück zur ursprünglichen Enum-Definition
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open'");
    }
};