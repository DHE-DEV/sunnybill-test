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
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->string('external_contract_number')->nullable()->after('creditor_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn('external_contract_number');
        });
    }
};
