<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_article', function (Blueprint $table) {
            $table->foreignUuid('supplier_id')
                ->nullable()
                ->after('solar_plant_id')
                ->constrained('suppliers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_article', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
