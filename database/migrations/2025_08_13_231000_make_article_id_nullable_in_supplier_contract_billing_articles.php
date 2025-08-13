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
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign('scb_articles_article_fk');
            
            // Make article_id nullable
            $table->uuid('article_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable
            $table->foreign('article_id', 'scb_articles_article_fk')
                ->references('id')
                ->on('articles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign('scb_articles_article_fk');
            
            // Make article_id not nullable again
            $table->uuid('article_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('article_id', 'scb_articles_article_fk')
                ->references('id')
                ->on('articles')
                ->cascadeOnDelete();
        });
    }
};
