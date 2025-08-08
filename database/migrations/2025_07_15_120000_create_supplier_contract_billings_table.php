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
        Schema::create('supplier_contract_billings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_contract_id');
            $table->string('billing_number')->unique();
            $table->string('supplier_invoice_number')->nullable();
            $table->enum('billing_type', ['invoice', 'credit_note'])->default('invoice');
            $table->year('billing_year')->nullable();
            $table->unsignedTinyInteger('billing_month')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('billing_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 15, 6)->default(0);
            $table->decimal('net_amount', 15, 6)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable()->default(19.00);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('supplier_contract_id')
                ->references('id')
                ->on('supplier_contracts')
                ->onDelete('cascade');
                
            // Indexes
            $table->index('supplier_contract_id', 'scb_supplier_contract_idx');
            $table->index(['billing_year', 'billing_month'], 'scb_billing_period_idx');
            $table->index('billing_type', 'scb_billing_type_idx');
            $table->index('status', 'scb_status_idx');
            $table->index('supplier_invoice_number', 'scb_supplier_invoice_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contract_billings');
    }
};
