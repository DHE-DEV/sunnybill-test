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
            $table->string('supplier_invoice_number')->nullable()->after('billing_number');
            $table->index('supplier_invoice_number', 'scb_supplier_invoice_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            $table->dropIndex('scb_supplier_invoice_idx');
            $table->dropColumn('supplier_invoice_number');
        });
    }
};