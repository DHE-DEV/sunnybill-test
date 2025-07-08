<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gmail_emails', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid');
        });

        // Generiere UUIDs für bestehende Einträge
        DB::table('gmail_emails')->whereNull('uuid')->chunkById(100, function ($emails) {
            foreach ($emails as $email) {
                DB::table('gmail_emails')
                    ->where('id', $email->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        // Mache UUID required nach dem Befüllen
        Schema::table('gmail_emails', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gmail_emails', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
