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
        Schema::table('articles', function (Blueprint $table) {
            // Neues Feld für die Beziehung zu TaxRate
            $table->uuid('tax_rate_id')->nullable()->after('price');
            
            // Foreign Key Constraint
            $table->foreign('tax_rate_id')->references('id')->on('tax_rates')->onDelete('set null');
            
            // Index für bessere Performance
            $table->index('tax_rate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropIndex(['tax_rate_id']);
            $table->dropColumn('tax_rate_id');
        });
    }
};