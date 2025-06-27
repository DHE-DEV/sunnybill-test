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
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('addressable_id'); // Polymorphe Beziehung
            $table->string('addressable_type'); // Customer, Supplier, etc.
            $table->enum('type', ['standard', 'billing', 'shipping'])->default('standard');
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('street_address');
            $table->string('postal_code', 10);
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('Deutschland');
            $table->string('label')->nullable(); // Zusätzliche Beschreibung
            $table->boolean('is_primary')->default(false); // Hauptadresse pro Typ
            $table->timestamps();
            
            $table->index(['addressable_id', 'addressable_type']);
            $table->index('type');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};