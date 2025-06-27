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
        Schema::table('solar_plant_notes', function (Blueprint $table) {
            // Erweitere das type Enum um neue Werte
            $table->enum('type', [
                'general',
                'maintenance', 
                'issue',
                'improvement',
                'planning',
                'installation',
                'commissioning',
                'monitoring',
                'performance',
                'financial'
            ])->default('general')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_notes', function (Blueprint $table) {
            // Zurück zu den ursprünglichen Werten
            $table->enum('type', ['general', 'maintenance', 'issue', 'improvement'])
                ->default('general')->change();
        });
    }
};