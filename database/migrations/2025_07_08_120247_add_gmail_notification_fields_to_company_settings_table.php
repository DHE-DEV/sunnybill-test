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
            // Gmail Push-Benachrichtigungseinstellungen
            $table->boolean('gmail_notifications_enabled')->default(false)->after('gmail_max_results');
            $table->json('gmail_notification_users')->nullable()->after('gmail_notifications_enabled');
            $table->json('gmail_notification_types')->nullable()->after('gmail_notification_users');
            $table->json('gmail_notification_filters')->nullable()->after('gmail_notification_types');
            $table->string('gmail_notification_template')->nullable()->after('gmail_notification_filters');
            $table->json('gmail_notification_schedule')->nullable()->after('gmail_notification_template');
            $table->boolean('gmail_notification_sound')->default(true)->after('gmail_notification_schedule');
            $table->integer('gmail_notification_duration')->default(5000)->after('gmail_notification_sound');
            $table->timestamp('gmail_notifications_last_sent')->nullable()->after('gmail_notification_duration');
            $table->integer('gmail_notifications_sent_count')->default(0)->after('gmail_notifications_last_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'gmail_notifications_enabled',
                'gmail_notification_users',
                'gmail_notification_types',
                'gmail_notification_filters',
                'gmail_notification_template',
                'gmail_notification_schedule',
                'gmail_notification_sound',
                'gmail_notification_duration',
                'gmail_notifications_last_sent',
                'gmail_notifications_sent_count',
            ]);
        });
    }
};
