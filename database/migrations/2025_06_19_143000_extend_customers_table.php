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
            // Erweiterte Kundendaten
            $table->string('company_name')->nullable()->after('name');
            $table->string('contact_person')->nullable()->after('company_name');
            $table->string('website')->nullable()->after('email');
            $table->string('tax_number')->nullable()->after('country');
            $table->string('vat_id')->nullable()->after('tax_number');
            $table->text('notes')->nullable()->after('vat_id');
            $table->datetime('lexoffice_synced_at')->nullable()->after('lexoffice_id');
            $table->boolean('is_active')->default(true)->after('notes');
            $table->enum('customer_type', ['business', 'private'])->default('business')->after('is_active');
            
            // Indizes
            $table->index(['customer_type', 'is_active']);
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['customer_type', 'is_active']);
            $table->dropIndex(['company_name']);
            
            $table->dropColumn([
                'company_name',
                'contact_person', 
                'website',
                'tax_number',
                'vat_id',
                'notes',
                'lexoffice_synced_at',
                'is_active',
                'customer_type'
            ]);
        });
    }
};