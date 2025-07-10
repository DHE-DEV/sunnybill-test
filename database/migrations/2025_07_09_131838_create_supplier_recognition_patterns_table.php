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
        Schema::create('supplier_recognition_patterns', function (Blueprint $table) {
            $table->id();
            $table->char('supplier_id', 36)->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->enum('pattern_type', [
                'email_domain',
                'company_name', 
                'tax_id',
                'bank_account',
                'sender_email',
                'pdf_text_contains',
                'invoice_format'
            ]);
            $table->string('pattern_value');
            $table->text('description')->nullable();
            $table->integer('confidence_weight')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'is_active']);
            $table->index(['pattern_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_recognition_patterns');
    }
};
