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
        Schema::create('solar_plant_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Standard-Status einfügen
        DB::table('solar_plant_statuses')->insert([
            [
                'key' => 'in_planning',
                'name' => 'In Planung',
                'description' => 'Anlage befindet sich in der Planungsphase',
                'color' => 'gray',
                'sort_order' => 1,
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'planned',
                'name' => 'Geplant',
                'description' => 'Anlage ist geplant und genehmigt',
                'color' => 'info',
                'sort_order' => 2,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'under_construction',
                'name' => 'Im Bau',
                'description' => 'Anlage wird derzeit gebaut',
                'color' => 'warning',
                'sort_order' => 3,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'awaiting_commissioning',
                'name' => 'Warte auf Inbetriebnahme',
                'description' => 'Anlage ist fertig gebaut und wartet auf Inbetriebnahme',
                'color' => 'primary',
                'sort_order' => 4,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'active',
                'name' => 'Aktiv',
                'description' => 'Anlage ist in Betrieb und produziert Strom',
                'color' => 'success',
                'sort_order' => 5,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance',
                'name' => 'Wartung',
                'description' => 'Anlage befindet sich in Wartung',
                'color' => 'info',
                'sort_order' => 6,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'inactive',
                'name' => 'Inaktiv',
                'description' => 'Anlage ist außer Betrieb',
                'color' => 'danger',
                'sort_order' => 7,
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_statuses');
    }
};
