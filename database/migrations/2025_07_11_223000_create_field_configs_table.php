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
        Schema::create('field_configs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // supplier_contract, customer, supplier, solar_plant
            $table->string('field_key'); // title, description, supplier_id, etc.
            $table->string('field_label');
            $table->text('field_description')->nullable();
            $table->string('field_type'); // text, textarea, select, date, number, toggle, etc.
            $table->json('field_options')->nullable(); // Für Select-Optionen, Validierungsregeln, etc.
            $table->string('section_name')->nullable(); // Vertragsdaten, Laufzeit & Wert, etc.
            $table->integer('section_sort_order')->default(0); // Reihenfolge der Sections
            $table->integer('sort_order')->default(0); // Reihenfolge innerhalb der Section
            $table->integer('column_span')->default(1); // 1 = halbe Breite, 2 = volle Breite
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_field')->default(false); // Unterscheidung zwischen System- und Custom-Feldern
            $table->timestamps();
            
            // Unique constraint für entity_type + field_key
            $table->unique(['entity_type', 'field_key']);
            
            // Indizes für Performance
            $table->index(['entity_type', 'is_active']);
            $table->index(['entity_type', 'section_name', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_configs');
    }
};