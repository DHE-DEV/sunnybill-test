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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('ranking', ['A', 'B', 'C', 'D', 'E'])->nullable()->after('customer_type');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->enum('ranking', ['A', 'B', 'C', 'D', 'E'])->nullable()->after('supplier_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('ranking');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('ranking');
        });
    }
};
