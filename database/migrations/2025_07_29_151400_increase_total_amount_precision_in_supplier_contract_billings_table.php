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
        if (Schema::hasTable('supplier_contract_billings')) {
            Schema::table('supplier_contract_billings', function (Blueprint $table) {
                // Erweitere total_amount für größere Beträge
                $table->decimal('total_amount', 15, 6)->change();
                
                // Erweitere auch net_amount entsprechend (falls vorhanden)
                if (Schema::hasColumn('supplier_contract_billings', 'net_amount')) {
                    $table->decimal('net_amount', 15, 6)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            // Zurück zu ursprünglichen Werten
            $table->decimal('total_amount', 10, 2)->change();
            
            if (Schema::hasColumn('supplier_contract_billings', 'net_amount')) {
                $table->decimal('net_amount', 15, 2)->nullable()->change();
            }
        });
    }
};
