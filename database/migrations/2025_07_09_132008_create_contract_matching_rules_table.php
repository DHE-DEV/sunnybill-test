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
        Schema::create('contract_matching_rules', function (Blueprint $table) {
            $table->id();
            $table->char('supplier_contract_id', 36);
            $table->foreign('supplier_contract_id')->references('id')->on('supplier_contracts')->onDelete('cascade');
            $table->enum('field_source', [
                'pdf_text',
                'email_text',
                'extracted_data',
                'email_subject',
                'sender_email'
            ]);
            $table->string('field_name')->nullable(); // Für extracted_data: welches Feld
            $table->text('matching_pattern'); // Pattern oder exakter Wert
            $table->enum('match_type', [
                'exact',
                'contains',
                'regex',
                'starts_with',
                'ends_with'
            ])->default('contains');
            $table->text('description')->nullable();
            $table->integer('confidence_weight')->default(10);
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_contract_id', 'is_active']);
            $table->index(['field_source', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_matching_rules');
    }
};
