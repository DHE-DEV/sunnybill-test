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
        Schema::create('billing_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Beziehung zur Solaranlage (immer erforderlich)
            $table->foreignUuid('solar_plant_id')->constrained('solar_plants')->cascadeOnDelete();

            // Beziehung zum Lieferantenvertrag (optional für fehlende Abrechnungen)
            $table->foreignUuid('supplier_contract_id')->nullable()->constrained('supplier_contracts')->cascadeOnDelete();

            // Monat und Jahr der Abrechnung
            $table->integer('billing_year');
            $table->integer('billing_month');

            // Notiz-Inhalt
            $table->text('note');

            // Benutzer, der die Notiz erstellt hat
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indizes für schnellere Suche
            $table->index(['solar_plant_id', 'billing_year', 'billing_month']);
            $table->index('supplier_contract_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_notes');
    }
};
