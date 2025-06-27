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
        Schema::table('customer_notes', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('content');
            $table->integer('sort_order')->default(0)->after('is_favorite');
            $table->string('created_by')->nullable()->after('sort_order');
            
            $table->index(['customer_id', 'is_favorite', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_notes', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'is_favorite', 'sort_order']);
            $table->dropColumn(['is_favorite', 'sort_order', 'created_by']);
        });
    }
};