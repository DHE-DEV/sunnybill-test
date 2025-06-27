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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('invoice_id');
            $table->uuid('article_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 6); // Einzelpreis mit 6 Nachkommastellen
            $table->decimal('tax_rate', 4, 2); // Steuersatz
            $table->decimal('total', 15, 6); // Gesamtpreis mit 6 Nachkommastellen
            $table->text('description')->nullable(); // Zusätzliche Beschreibung
            $table->timestamps();
            
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            
            $table->index(['invoice_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
