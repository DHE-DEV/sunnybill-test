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
        Schema::create('document_path_settings', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type'); // z.B. 'App\Models\Supplier'
            $table->string('category')->nullable(); // z.B. 'contracts', 'invoices', etc.
            $table->string('path_template'); // z.B. 'lieferanten/{supplier_number}/vertraege/{contract_internal_number}'
            $table->json('placeholders')->nullable(); // Verfügbare Platzhalter für diesen Typ
            $table->text('description')->nullable(); // Beschreibung der Pfadkonfiguration
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index für bessere Performance (mit kurzem Namen)
            $table->index(['documentable_type', 'category', 'is_active'], 'doc_path_type_cat_active_idx');
            
            // Eindeutige Kombination pro Typ und Kategorie
            $table->unique(['documentable_type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_path_settings');
    }
};