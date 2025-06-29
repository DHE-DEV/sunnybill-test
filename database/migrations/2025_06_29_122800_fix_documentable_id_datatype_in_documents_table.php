<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Entferne den Index falls vorhanden
            $table->dropIndex(['documentable_type', 'documentable_id']);
            
            // Ändere documentable_id von unsignedBigInteger zu string für UUID-Unterstützung
            $table->string('documentable_id')->change();
            
            // Erstelle den Index neu
            $table->index(['documentable_type', 'documentable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Entferne den Index
            $table->dropIndex(['documentable_type', 'documentable_id']);
            
            // Ändere zurück zu unsignedBigInteger
            $table->unsignedBigInteger('documentable_id')->change();
            
            // Erstelle den Index neu
            $table->index(['documentable_type', 'documentable_id']);
        });
    }
};