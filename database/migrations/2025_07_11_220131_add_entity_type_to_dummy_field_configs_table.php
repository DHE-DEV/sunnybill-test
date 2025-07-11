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
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            $table->string('entity_type')->default('supplier_contract')->after('id');
            $table->index(['entity_type', 'is_active']);
        });
        
        // Bestehende Einträge auf supplier_contract setzen
        DB::table('dummy_field_configs')->update(['entity_type' => 'supplier_contract']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dummy_field_configs', function (Blueprint $table) {
            $table->dropIndex(['entity_type', 'is_active']);
            $table->dropColumn('entity_type');
        });
    }
};
