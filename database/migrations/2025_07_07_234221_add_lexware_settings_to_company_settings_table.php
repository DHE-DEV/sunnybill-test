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
        Schema::table('company_settings', function (Blueprint $table) {
            // Lexware/Lexoffice API Einstellungen
            $table->boolean('lexware_sync_enabled')->default(false)->after('portal_enabled');
            $table->string('lexware_api_url')->nullable()->after('lexware_sync_enabled');
            $table->string('lexware_api_key')->nullable()->after('lexware_api_url');
            $table->string('lexware_organization_id')->nullable()->after('lexware_api_key');
            
            // Zusätzliche Sync-Einstellungen
            $table->boolean('lexware_auto_sync_customers')->default(true)->after('lexware_organization_id');
            $table->boolean('lexware_auto_sync_addresses')->default(true)->after('lexware_auto_sync_customers');
            $table->boolean('lexware_import_customer_numbers')->default(true)->after('lexware_auto_sync_addresses');
            
            // Logging und Debugging
            $table->boolean('lexware_debug_logging')->default(false)->after('lexware_import_customer_numbers');
            $table->timestamp('lexware_last_sync')->nullable()->after('lexware_debug_logging');
            $table->text('lexware_last_error')->nullable()->after('lexware_last_sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'lexware_sync_enabled',
                'lexware_api_url',
                'lexware_api_key',
                'lexware_organization_id',
                'lexware_auto_sync_customers',
                'lexware_auto_sync_addresses',
                'lexware_import_customer_numbers',
                'lexware_debug_logging',
                'lexware_last_sync',
                'lexware_last_error'
            ]);
        });
    }
};
