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
        Schema::create('supplier_contract_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_contract_id');
            $table->string('title');
            $table->text('content');
            $table->string('created_by')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('supplier_contract_id')->references('id')->on('supplier_contracts')->onDelete('cascade');
            $table->index('supplier_contract_id');
            $table->index('is_favorite');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contract_notes');
    }
};