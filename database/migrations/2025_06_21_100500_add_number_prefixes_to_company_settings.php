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
        Schema::table('company_settings', function (Blueprint $table) {
            // Präfixe für Nummernformate
            $table->string('customer_number_prefix')->nullable()->after('total_price_decimal_places');
            $table->string('supplier_number_prefix')->nullable()->after('customer_number_prefix');
            $table->string('invoice_number_prefix')->nullable()->after('supplier_number_prefix');
            $table->boolean('invoice_number_include_year')->default(false)->after('invoice_number_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'customer_number_prefix',
                'supplier_number_prefix', 
                'invoice_number_prefix',
                'invoice_number_include_year'
            ]);
        });
    }
};