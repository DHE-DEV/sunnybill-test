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
            $table->year('billing_year')->nullable()->after('billing_type');
            $table->tinyInteger('billing_month')->nullable()->after('billing_year');
            
            $table->index(['billing_year', 'billing_month'], 'scb_billing_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            $table->dropIndex('scb_billing_period_idx');
            $table->dropColumn(['billing_year', 'billing_month']);
        });
    }
};
