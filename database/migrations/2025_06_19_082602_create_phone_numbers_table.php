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
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('phoneable_id'); // Polymorphe Beziehung
            $table->string('phoneable_type'); // Customer, SupplierEmployee, etc.
            $table->string('phone_number');
            $table->enum('type', ['business', 'private', 'mobile'])->default('business');
            $table->string('label')->nullable(); // Zusätzliche Beschreibung
            $table->boolean('is_primary')->default(false); // Hauptnummer
            $table->timestamps();
            
            $table->index(['phoneable_id', 'phoneable_type']);
            $table->index('type');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};