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
            // Erhöhe die Präzision der Koordinatenfelder für Google Maps Genauigkeit
            // decimal(11, 8) erlaubt Werte wie 53.496851832624074 (11 Stellen gesamt, 8 Nachkommastellen)
            $table->decimal('latitude', 11, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            // Zurück zur ursprünglichen Präzision
            $table->decimal('latitude', 8, 6)->nullable()->change();
            $table->decimal('longitude', 8, 6)->nullable()->change();
        });
    }
};
