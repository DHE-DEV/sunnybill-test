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
        Schema::table('app_tokens', function (Blueprint $table) {
            // JSON-Spalten für Ressourcen-Beschränkungen
            $table->json('allowed_customers')->nullable()->comment('IDs der erlaubten Kunden (null = alle erlaubt)');
            $table->json('allowed_suppliers')->nullable()->comment('IDs der erlaubten Lieferanten (null = alle erlaubt)');
            $table->json('allowed_solar_plants')->nullable()->comment('IDs der erlaubten Solaranlagen (null = alle erlaubt)');
            $table->json('allowed_projects')->nullable()->comment('IDs der erlaubten Projekte (null = alle erlaubt)');
            
            // Boolean für globale Beschränkungen
            $table->boolean('restrict_customers')->default(false)->comment('Beschränke Zugriff auf bestimmte Kunden');
            $table->boolean('restrict_suppliers')->default(false)->comment('Beschränke Zugriff auf bestimmte Lieferanten');
            $table->boolean('restrict_solar_plants')->default(false)->comment('Beschränke Zugriff auf bestimmte Solaranlagen');
            $table->boolean('restrict_projects')->default(false)->comment('Beschränke Zugriff auf bestimmte Projekte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_tokens', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_customers',
                'allowed_suppliers', 
                'allowed_solar_plants',
                'allowed_projects',
                'restrict_customers',
                'restrict_suppliers',
                'restrict_solar_plants',
                'restrict_projects'
            ]);
        });
    }
};
