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
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('recipient_type')->default('user'); // 'user' oder 'team'
            
            $table->index(['team_id', 'recipient_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropIndex(['team_id', 'recipient_type']);
            $table->dropColumn(['team_id', 'recipient_type']);
        });
    }
};