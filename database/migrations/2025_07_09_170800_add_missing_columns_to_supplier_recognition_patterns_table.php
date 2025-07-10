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
        Schema::table('supplier_recognition_patterns', function (Blueprint $table) {
            // Fehlende Spalten für Filament Resource Kompatibilität
            $table->integer('priority')->default(1)->after('confidence_weight');
            $table->boolean('is_regex')->default(false)->after('priority');
            $table->boolean('case_sensitive')->default(false)->after('is_regex');
            $table->text('test_examples')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_recognition_patterns', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'is_regex',
                'case_sensitive',
                'test_examples',
            ]);
        });
    }
};