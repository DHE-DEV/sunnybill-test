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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->uuid('supplier_type_id')->nullable()->after('company_name');
            $table->foreign('supplier_type_id')->references('id')->on('supplier_types')->onDelete('set null');
            $table->index('supplier_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['supplier_type_id']);
            $table->dropIndex(['supplier_type_id']);
            $table->dropColumn('supplier_type_id');
        });
    }
};