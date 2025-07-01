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
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            $table->enum('billing_type', ['invoice', 'credit_note'])
                  ->default('invoice')
                  ->after('supplier_invoice_number');
            $table->index('billing_type', 'scb_billing_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            $table->dropIndex('scb_billing_type_idx');
            $table->dropColumn('billing_type');
        });
    }
};
