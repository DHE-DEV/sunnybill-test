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
        if (!Schema::hasTable('supplier_contract_billing_allocations')) {
            Schema::create('supplier_contract_billing_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_contract_billing_id');
            $table->uuid('solar_plant_id');
            $table->decimal('percentage', 5, 2)->default(0.00)->comment('Prozentsatz der Zuordnung');
            $table->decimal('amount', 15, 6)->default(0.00)->comment('Betrag basierend auf Prozentsatz');
            $table->text('notes')->nullable()->comment('Zusätzliche Notizen');
            $table->boolean('is_active')->default(true)->comment('Aktiv/Inaktiv Status');
            $table->timestamps();
            $table->softDeletes();
            
            // Custom named foreign keys to avoid length issues
            $table->foreign('supplier_contract_billing_id', 'scba_billing_fk')
                  ->references('id')->on('supplier_contract_billings')
                  ->onDelete('cascade');
            
            $table->foreign('solar_plant_id', 'scba_solar_plant_fk')
                  ->references('id')->on('solar_plants')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index(['supplier_contract_billing_id'], 'scba_billing_idx');
            $table->index(['solar_plant_id'], 'scba_solar_plant_idx');
            $table->index(['is_active'], 'scba_active_idx');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contract_billing_allocations');
    }
};
