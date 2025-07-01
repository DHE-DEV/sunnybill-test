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
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('billing_date');
            $table->date('due_date')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_contract_id')->references('id')->on('supplier_contracts')->onDelete('cascade');
            $table->index(['supplier_contract_id', 'billing_date'], 'scb_contract_date_idx');
            $table->index(['status', 'billing_date'], 'scb_status_date_idx');
            $table->index('billing_number', 'scb_billing_number_idx');
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
