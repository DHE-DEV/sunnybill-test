<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            // Status-Enum erweitern um neue Optionen
            $table->enum('status', [
                'in_planning',           // In Planung
                'planned',               // Geplant (bestehend)
                'under_construction',    // Im Bau (bestehend)
                'awaiting_commissioning', // Warte auf Inbetriebnahme
                'active',                // Aktiv (bestehend)
                'maintenance',           // Wartung (bestehend)
                'inactive'               // Inaktiv (bestehend)
            ])->default('in_planning')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            // Zurück zu den ursprünglichen Status-Optionen
            $table->enum('status', ['planned', 'under_construction', 'active', 'maintenance', 'inactive'])
                ->default('planned')->change();
        });
    }
};