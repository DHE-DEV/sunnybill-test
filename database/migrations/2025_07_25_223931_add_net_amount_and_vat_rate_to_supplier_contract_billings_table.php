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
            $table->decimal('net_amount', 15, 2)->nullable()->after('total_amount')->comment('Nettobetrag vor MwSt.');
            $table->decimal('vat_rate', 5, 2)->nullable()->default(19.00)->after('net_amount')->comment('Mehrwertsteuersatz in Prozent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billings', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'vat_rate']);
        });
    }
};
