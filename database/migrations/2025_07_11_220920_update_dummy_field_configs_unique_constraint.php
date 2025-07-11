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
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            // Alten unique constraint auf field_key entfernen
            $table->dropUnique(['field_key']);
            
            // Neuen composite unique constraint auf entity_type und field_key hinzufügen
            $table->unique(['entity_type', 'field_key'], 'dummy_field_configs_entity_field_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            // Composite unique constraint entfernen
            $table->dropUnique('dummy_field_configs_entity_field_unique');
            
            // Alten unique constraint auf field_key wiederherstellen
            $table->unique('field_key');
        });
    }
};
