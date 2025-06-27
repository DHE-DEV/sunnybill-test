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
        Schema::create('supplier_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('position')->nullable(); // Position/Abteilung
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary_contact')->default(false); // Hauptansprechpartner
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->index(['supplier_id', 'is_active']);
            $table->index('is_primary_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_employees');
    }
};