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
        Schema::create('pdf_extraction_rules', function (Blueprint $table) {
            $table->id();
            $table->char('supplier_id', 36)->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->string('field_name', 100); // z.B. 'invoice_number', 'amount', 'date'
            $table->enum('extraction_method', [
                'regex',
                'keyword_search',
                'position_based',
                'zugferd',
                'line_after_keyword',
                'between_keywords'
            ]);
            $table->text('pattern'); // Regex Pattern oder Keyword
            $table->text('fallback_pattern')->nullable(); // Alternative Pattern
            $table->text('description')->nullable();
            $table->integer('priority')->default(1); // Niedrigere Zahl = höhere Priorität
            $table->json('options')->nullable(); // Zusätzliche Optionen (z.B. case_sensitive, multiline)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'is_active']);
            $table->index(['field_name', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_extraction_rules');
    }
};
