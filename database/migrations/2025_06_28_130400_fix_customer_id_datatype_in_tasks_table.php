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
        Schema::table('tasks', function (Blueprint $table) {
            // Entferne den bestehenden Index falls vorhanden
            $table->dropIndex(['customer_id', 'status']);
            
            // Ändere den Datentyp von customer_id von unsignedBigInteger zu char(36) für UUID
            $table->char('customer_id', 36)->nullable()->change();
            
            // Füge den Foreign Key Constraint hinzu
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            
            // Erstelle den Index neu
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Entferne Foreign Key und Index
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id', 'status']);
            
            // Ändere zurück zu unsignedBigInteger
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            
            // Erstelle den Index neu
            $table->index(['customer_id', 'status']);
        });
    }
};