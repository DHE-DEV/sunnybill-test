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
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            // Erweitere quantity von decimal(10,4) auf decimal(15,4) für größere Mengen
            $table->decimal('quantity', 15, 4)->default(1)->change();
            
            // Erweitere auch total_price für entsprechend größere Gesamtpreise
            $table->decimal('total_price', 15, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            // Zurück zu ursprünglichen Werten
            $table->decimal('quantity', 10, 4)->default(1)->change();
            $table->decimal('total_price', 10, 2)->change();
        });
    }
};
