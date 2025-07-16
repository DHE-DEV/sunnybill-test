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
        Schema::create('supplier_contract_billing_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_contract_billing_id');
            $table->uuid('article_id');
            $table->decimal('quantity', 10, 4)->default(1);
            $table->decimal('unit_price', 10, 6);
            $table->decimal('total_price', 10, 2);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('supplier_contract_billing_id', 'scb_articles_billing_fk')
                ->references('id')
                ->on('supplier_contract_billings')
                ->cascadeOnDelete();
            
            $table->foreign('article_id', 'scb_articles_article_fk')
                ->references('id')
                ->on('articles')
                ->cascadeOnDelete();
            
            $table->index(['supplier_contract_billing_id', 'article_id'], 'scb_articles_composite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contract_billing_articles');
    }
};
