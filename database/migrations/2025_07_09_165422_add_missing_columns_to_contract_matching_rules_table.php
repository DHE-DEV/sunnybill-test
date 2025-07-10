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
        Schema::table('contract_matching_rules', function (Blueprint $table) {
            // Fehlende Spalten hinzufügen, die das Filament Resource erwartet
            $table->string('rule_name')->nullable()->after('supplier_contract_id');
            $table->string('source_field')->nullable()->after('rule_name');
            $table->string('target_field')->nullable()->after('source_field');
            $table->decimal('match_threshold', 3, 2)->default(0.8)->after('confidence_weight');
            $table->text('match_pattern')->nullable()->after('match_threshold');
            $table->integer('priority')->default(1)->after('match_pattern');
            $table->boolean('normalize_whitespace')->default(true)->after('case_sensitive');
            $table->boolean('remove_special_chars')->default(false)->after('normalize_whitespace');
            $table->text('preprocessing_rules')->nullable()->after('remove_special_chars');
            $table->text('fallback_rules')->nullable()->after('preprocessing_rules');
            $table->text('test_examples')->nullable()->after('fallback_rules');
            
            // Index für priority hinzufügen
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_matching_rules', function (Blueprint $table) {
            // Index entfernen
            $table->dropIndex(['priority']);
            
            // Spalten entfernen
            $table->dropColumn([
                'rule_name',
                'source_field', 
                'target_field',
                'match_threshold',
                'match_pattern',
                'priority',
                'normalize_whitespace',
                'remove_special_chars',
                'preprocessing_rules',
                'fallback_rules',
                'test_examples'
            ]);
        });
    }
};
