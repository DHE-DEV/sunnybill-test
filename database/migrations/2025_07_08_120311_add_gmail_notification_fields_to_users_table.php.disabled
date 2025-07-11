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
        Schema::table('users', function (Blueprint $table) {
            // User-spezifische Gmail-Benachrichtigungseinstellungen
            $table->boolean('gmail_notifications_enabled')->default(true)->after('password_changed_at');
            $table->json('gmail_notification_preferences')->nullable()->after('gmail_notifications_enabled');
            $table->boolean('gmail_browser_notifications')->default(true)->after('gmail_notification_preferences');
            $table->boolean('gmail_email_notifications')->default(false)->after('gmail_browser_notifications');
            $table->boolean('gmail_sound_notifications')->default(true)->after('gmail_email_notifications');
            $table->timestamp('gmail_last_notification_at')->nullable()->after('gmail_sound_notifications');
            $table->integer('gmail_notifications_received_count')->default(0)->after('gmail_last_notification_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gmail_notifications_enabled',
                'gmail_notification_preferences',
                'gmail_browser_notifications',
                'gmail_email_notifications',
                'gmail_sound_notifications',
                'gmail_last_notification_at',
                'gmail_notifications_received_count',
            ]);
        });
    }
};
