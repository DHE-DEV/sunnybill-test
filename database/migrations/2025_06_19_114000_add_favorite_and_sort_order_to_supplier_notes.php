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
        Schema::table('supplier_notes', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('content');
            $table->integer('sort_order')->default(0)->after('is_favorite');
            
            $table->index(['supplier_id', 'is_favorite', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_notes', function (Blueprint $table) {
            $table->dropIndex(['supplier_id', 'is_favorite', 'sort_order']);
            $table->dropColumn(['is_favorite', 'sort_order']);
        });
    }
};