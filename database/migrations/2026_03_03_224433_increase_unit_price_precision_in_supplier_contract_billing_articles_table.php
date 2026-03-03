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
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 6)->change();
        });
    }
};
