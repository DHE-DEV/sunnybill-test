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
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 6); // Interner Preis mit 6 Nachkommastellen
            $table->decimal('tax_rate', 4, 2)->default(0.19); // Steuersatz (z.B. 0.19 für 19%)
            $table->uuid('lexoffice_id')->nullable();
            $table->timestamps();
            
            $table->index('lexoffice_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
