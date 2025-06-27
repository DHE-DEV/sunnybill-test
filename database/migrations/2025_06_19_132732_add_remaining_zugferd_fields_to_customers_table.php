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
        Schema::table('customers', function (Blueprint $table) {
            // Prüfe und füge nur fehlende Felder hinzu
            if (!Schema::hasColumn('customers', 'customer_number')) {
                $table->string('customer_number')->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'department')) {
                $table->string('department')->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'fax')) {
                $table->string('fax')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('street');
            }
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'country_code')) {
                $table->string('country_code', 2)->default('DE')->after('country');
            }
            if (!Schema::hasColumn('customers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('customers', 'payment_days')) {
                $table->integer('payment_days')->default(14)->after('payment_terms');
            }
            if (!Schema::hasColumn('customers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('payment_days');
            }
            if (!Schema::hasColumn('customers', 'iban')) {
                $table->string('iban')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('customers', 'bic')) {
                $table->string('bic')->nullable()->after('iban');
            }
            
            // Indizes nur hinzufügen, wenn Spalten existieren
            if (Schema::hasColumn('customers', 'customer_number')) {
                $table->index('customer_number');
            }
            if (Schema::hasColumn('customers', 'country_code')) {
                $table->index('country_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Entferne Indizes falls vorhanden
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('customers');
            
            if (isset($indexes['customers_customer_number_index'])) {
                $table->dropIndex(['customer_number']);
            }
            if (isset($indexes['customers_country_code_index'])) {
                $table->dropIndex(['country_code']);
            }
            
            // Entferne Spalten falls vorhanden
            $columnsToRemove = [
                'customer_number',
                'department',
                'fax',
                'address_line_2',
                'state',
                'country_code',
                'payment_terms',
                'payment_days',
                'bank_name',
                'iban',
                'bic'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
