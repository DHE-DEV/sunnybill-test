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
        Schema::create('supplier_article', function (Blueprint $table) {
            $table->id();
            $table->uuid('supplier_id');
            $table->uuid('article_id');
            $table->decimal('quantity', 15, 6)->default(1.00);
            $table->decimal('unit_price', 15, 6)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');

            $table->index('supplier_id');
            $table->index('article_id');
            $table->index('is_active');

            $table->unique(['supplier_id', 'article_id'], 'unique_supplier_article');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_article');
    }
};
