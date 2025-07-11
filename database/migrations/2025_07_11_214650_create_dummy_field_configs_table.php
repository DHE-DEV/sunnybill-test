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
        Schema::create('dummy_field_configs', function (Blueprint $table) {
            $table->id();
            $table->string('field_key')->unique(); // z.B. 'custom_field_1'
            $table->string('field_label')->default(''); // z.B. 'Zusatzfeld 1'
            $table->text('field_description')->nullable(); // Hilfetext
            $table->boolean('is_active')->default(false); // Aktiviert/Deaktiviert
            $table->integer('sort_order')->default(0); // Reihenfolge
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dummy_field_configs');
    }
};
