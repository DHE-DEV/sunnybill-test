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
        Schema::create('solar_plant_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solar_plant_id');
            $table->uuid('supplier_id');
            $table->uuid('supplier_employee_id')->nullable(); // Spezifischer Ansprechpartner
            $table->string('role')->nullable(); // Rolle des Lieferanten (z.B. "Installateur", "Wartung", "Komponenten")
            $table->text('notes')->nullable(); // Spezielle Notiz für diese Zuordnung
            $table->date('start_date')->nullable(); // Beginn der Zusammenarbeit
            $table->date('end_date')->nullable(); // Ende der Zusammenarbeit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('supplier_employee_id')->references('id')->on('supplier_employees')->onDelete('set null');
            
            $table->index(['solar_plant_id', 'is_active']);
            $table->index(['supplier_id', 'is_active']);
            $table->unique(['solar_plant_id', 'supplier_id', 'supplier_employee_id'], 'unique_plant_supplier_employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_suppliers');
    }
};