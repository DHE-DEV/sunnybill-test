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
        Schema::table('suppliers', function (Blueprint $table) {
            // Prüfe ob Spalten bereits existieren, bevor sie hinzugefügt werden
            if (!Schema::hasColumn('suppliers', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('suppliers', 'supplier_number')) {
                $table->string('supplier_number')->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('suppliers', 'department')) {
                $table->string('department')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('suppliers', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('suppliers', 'fax')) {
                $table->string('fax')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('suppliers', 'commercial_register')) {
                $table->string('commercial_register')->nullable()->after('vat_id');
            }
            if (!Schema::hasColumn('suppliers', 'street')) {
                $table->string('street')->nullable()->after('commercial_register');
            }
            if (!Schema::hasColumn('suppliers', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('street');
            }
            if (!Schema::hasColumn('suppliers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('suppliers', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('country');
            }
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('suppliers', 'iban')) {
                $table->string('iban', 34)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'bic')) {
                $table->string('bic', 11)->nullable()->after('iban');
            }
            if (!Schema::hasColumn('suppliers', 'account_holder')) {
                $table->string('account_holder')->nullable()->after('bic');
            }
            if (!Schema::hasColumn('suppliers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('account_holder');
            }
            if (!Schema::hasColumn('suppliers', 'payment_days')) {
                $table->integer('payment_days')->default(30)->after('payment_terms');
            }
            if (!Schema::hasColumn('suppliers', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('payment_days');
            }
            if (!Schema::hasColumn('suppliers', 'discount_days')) {
                $table->integer('discount_days')->nullable()->after('discount_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columnsToRemove = [
                'name', 'supplier_number', 'department', 'phone', 'fax', 
                'commercial_register', 'street', 'address_line_2', 'state', 
                'country_code', 'bank_name', 'iban', 'bic', 'account_holder',
                'payment_terms', 'payment_days', 'discount_percentage', 'discount_days'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};