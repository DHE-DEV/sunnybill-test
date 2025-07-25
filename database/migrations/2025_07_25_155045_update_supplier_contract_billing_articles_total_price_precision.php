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
            // Ändere total_price von decimal(8,2) auf decimal(12,6) für höhere Präzision
            $table->decimal('total_price', 12, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_billing_articles', function (Blueprint $table) {
            // Zurück zu decimal(12,2)
            $table->decimal('total_price', 12, 2)->change();
        });
    }
};
