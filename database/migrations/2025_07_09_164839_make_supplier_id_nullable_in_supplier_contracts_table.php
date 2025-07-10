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
        Schema::table('supplier_contracts', function (Blueprint $table) {
            // Entferne zuerst den Foreign Key Constraint
            $table->dropForeign(['supplier_id']);
            
            // Ändere supplier_id zu nullable
            $table->uuid('supplier_id')->nullable()->change();
            
            // Füge den Foreign Key wieder hinzu, aber mit nullable Unterstützung
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contracts', function (Blueprint $table) {
            // Entferne den Foreign Key
            $table->dropForeign(['supplier_id']);
            
            // Ändere supplier_id zurück zu NOT NULL
            $table->uuid('supplier_id')->nullable(false)->change();
            
            // Füge den ursprünglichen Foreign Key wieder hinzu
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }
};
