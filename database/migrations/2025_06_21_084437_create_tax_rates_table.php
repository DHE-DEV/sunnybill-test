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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // z.B. "Regulärer Satz", "Ermäßigter Satz"
            $table->string('description')->nullable(); // Beschreibung
            $table->decimal('rate', 5, 4); // Steuersatz als Dezimalwert (z.B. 0.1900)
            $table->date('valid_from'); // Gültig ab
            $table->date('valid_until')->nullable(); // Gültig bis (null = unbegrenzt)
            $table->boolean('is_active')->default(true); // Aktiv/Inaktiv
            $table->boolean('is_default')->default(false); // Standard-Steuersatz
            $table->timestamps();
            
            $table->index(['valid_from', 'valid_until', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};