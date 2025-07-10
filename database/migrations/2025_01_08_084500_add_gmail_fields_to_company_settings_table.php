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
            // Gmail OAuth2 Konfiguration
            $table->boolean('gmail_enabled')->default(false)->after('portal_enabled');
            $table->string('gmail_client_id')->nullable()->after('gmail_enabled');
            $table->text('gmail_client_secret')->nullable()->after('gmail_client_id');
            $table->text('gmail_refresh_token')->nullable()->after('gmail_client_secret');
            $table->text('gmail_access_token')->nullable()->after('gmail_refresh_token');
            $table->timestamp('gmail_token_expires_at')->nullable()->after('gmail_access_token');
            $table->string('gmail_email_address')->nullable()->after('gmail_token_expires_at');
            
            // Gmail Sync Einstellungen
            $table->boolean('gmail_auto_sync')->default(true)->after('gmail_email_address');
            $table->integer('gmail_sync_interval')->default(5)->after('gmail_auto_sync'); // Minuten
            $table->boolean('gmail_download_attachments')->default(true)->after('gmail_sync_interval');
            $table->string('gmail_attachment_path')->default('gmail-attachments')->after('gmail_download_attachments');
            
            // Gmail Labels/Tags
            $table->json('gmail_labels')->nullable()->after('gmail_attachment_path');
            $table->string('gmail_default_label')->nullable()->after('gmail_labels');
            
            // Gmail Sync Status
            $table->timestamp('gmail_last_sync')->nullable()->after('gmail_default_label');
            $table->text('gmail_last_error')->nullable()->after('gmail_last_sync');
            $table->integer('gmail_total_emails')->default(0)->after('gmail_last_error');
            $table->integer('gmail_unread_emails')->default(0)->after('gmail_total_emails');
            
            // Gmail Erweiterte Einstellungen
            $table->boolean('gmail_mark_as_read')->default(false)->after('gmail_unread_emails');
            $table->boolean('gmail_archive_processed')->default(false)->after('gmail_mark_as_read');
            $table->string('gmail_processed_label')->default('Processed')->after('gmail_archive_processed');
            $table->integer('gmail_max_results')->default(100)->after('gmail_processed_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'gmail_enabled',
                'gmail_client_id',
                'gmail_client_secret',
                'gmail_refresh_token',
                'gmail_access_token',
                'gmail_token_expires_at',
                'gmail_email_address',
                'gmail_auto_sync',
                'gmail_sync_interval',
                'gmail_download_attachments',
                'gmail_attachment_path',
                'gmail_labels',
                'gmail_default_label',
                'gmail_last_sync',
                'gmail_last_error',
                'gmail_total_emails',
                'gmail_unread_emails',
                'gmail_mark_as_read',
                'gmail_archive_processed',
                'gmail_processed_label',
                'gmail_max_results',
            ]);
        });
    }
};
