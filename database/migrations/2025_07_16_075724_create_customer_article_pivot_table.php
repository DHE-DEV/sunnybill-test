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
        Schema::create('customer_article', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->uuid('article_id');
            $table->decimal('quantity', 15, 6)->default(1.00);
            $table->decimal('unit_price', 15, 6)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('billing_requirement')->default('optional');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');

            $table->index('customer_id');
            $table->index('article_id');
            $table->index('is_active');

            $table->unique(['customer_id', 'article_id'], 'unique_customer_article');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_article');
    }
};
