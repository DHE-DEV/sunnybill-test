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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->uuid('article_version_id')->nullable()->after('article_id');
            $table->foreign('article_version_id')->references('id')->on('article_versions')->onDelete('set null');
            $table->index('article_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['article_version_id']);
            $table->dropIndex(['article_version_id']);
            $table->dropColumn('article_version_id');
        });
    }
};
