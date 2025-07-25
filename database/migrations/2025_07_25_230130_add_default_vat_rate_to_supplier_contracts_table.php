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
            $table->decimal('default_vat_rate', 5, 2)->default(19.00)->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            $table->dropColumn('default_vat_rate');
        });
    }
};
