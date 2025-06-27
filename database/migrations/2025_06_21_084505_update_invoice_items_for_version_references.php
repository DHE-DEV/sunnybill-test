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
            // Prüfe ob die Spalten bereits existieren
            if (!Schema::hasColumn('invoice_items', 'article_version_id')) {
                $table->uuid('article_version_id')->nullable()->after('article_id');
            }
            
            if (!Schema::hasColumn('invoice_items', 'tax_rate_version_id')) {
                $table->uuid('tax_rate_version_id')->nullable()->after('tax_rate');
            }
        });
        
        // Prüfe ob Foreign Key Constraints bereits existieren
        $foreignKeys = collect(\DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoice_items'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->pluck('CONSTRAINT_NAME')->toArray();
        
        Schema::table('invoice_items', function (Blueprint $table) use ($foreignKeys) {
            if (Schema::hasColumn('invoice_items', 'article_version_id') &&
                !in_array('invoice_items_article_version_id_foreign', $foreignKeys)) {
                $table->foreign('article_version_id')->references('id')->on('article_versions')->onDelete('set null');
            }
            
            if (Schema::hasColumn('invoice_items', 'tax_rate_version_id') &&
                !in_array('invoice_items_tax_rate_version_id_foreign', $foreignKeys)) {
                $table->foreign('tax_rate_version_id')->references('id')->on('tax_rate_versions')->onDelete('set null');
            }
        });
        
        // Prüfe ob Indizes bereits existieren
        $indexes = collect(\DB::select("SHOW INDEX FROM invoice_items"))->pluck('Key_name')->toArray();
        
        Schema::table('invoice_items', function (Blueprint $table) use ($indexes) {
            if (Schema::hasColumn('invoice_items', 'article_version_id') &&
                !in_array('invoice_items_article_version_id_index', $indexes)) {
                $table->index('article_version_id');
            }
            
            if (Schema::hasColumn('invoice_items', 'tax_rate_version_id') &&
                !in_array('invoice_items_tax_rate_version_id_index', $indexes)) {
                $table->index('tax_rate_version_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['article_version_id']);
            $table->dropForeign(['tax_rate_version_id']);
            $table->dropIndex(['article_version_id']);
            $table->dropIndex(['tax_rate_version_id']);
            $table->dropColumn(['article_version_id', 'tax_rate_version_id']);
        });
    }
};