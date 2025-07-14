<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Anzeigename (z.B. "Planung")
            $table->string('key')->unique(); // Eindeutiger Schlüssel (z.B. "planning")
            $table->text('description')->nullable(); // Beschreibung des Dokumententyps
            $table->string('color', 20)->default('gray'); // Farbe für Badges
            $table->string('icon', 50)->default('heroicon-o-document'); // Icon für Anzeige
            $table->boolean('is_active')->default(true); // Aktiv/Inaktiv
            $table->integer('sort_order')->default(0); // Sortierreihenfolge
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
