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
        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('table_name'); // z.B. 'solar_plants', 'supplier_contracts', etc.
            $table->json('filters')->nullable(); // Filter-Einstellungen
            $table->json('search')->nullable(); // Suchbegriffe
            $table->json('sort')->nullable(); // Sortierung
            $table->json('column_searches')->nullable(); // Spalten-spezifische Suchen
            $table->timestamps();
            
            $table->unique(['user_id', 'table_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_table_preferences');
    }
};
