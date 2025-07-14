<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Füge document_type_id hinzu (nullable für Übergangszeit)
            $table->foreignId('document_type_id')->nullable()->after('category')->constrained('document_types');
            
            // Index für bessere Performance
            $table->index('document_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropIndex(['document_type_id']);
            $table->dropColumn('document_type_id');
        });
    }
};
