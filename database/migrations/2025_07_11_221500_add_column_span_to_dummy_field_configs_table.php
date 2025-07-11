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
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            $table->integer('column_span')->default(1)->after('sort_order')
                ->comment('Spaltenbreite: 1 = halbe Breite, 2 = volle Breite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            $table->dropColumn('column_span');
        });
    }
};