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
        Schema::create('supplier_contract_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('supplier_contract_id');
            $table->uuid('article_id');
            $table->decimal('quantity', 15, 6)->default(1.00); // Menge
            $table->decimal('unit_price', 15, 6)->nullable(); // Stückpreis (kann vom Artikel abweichen)
            $table->text('notes')->nullable(); // Notizen zur Zuordnung
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('supplier_contract_id')->references('id')->on('supplier_contracts')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            
            // Indizes
            $table->index('supplier_contract_id');
            $table->index('article_id');
            $table->index('is_active');
            
            // Unique constraint um Duplikate zu vermeiden
            $table->unique(['supplier_contract_id', 'article_id'], 'unique_contract_article');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contract_articles');
    }
};
