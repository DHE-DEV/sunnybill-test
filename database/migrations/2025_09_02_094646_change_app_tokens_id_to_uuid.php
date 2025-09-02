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
        // First, we need to handle the auto-increment column properly
        \DB::statement('ALTER TABLE app_tokens MODIFY id BIGINT UNSIGNED NOT NULL');
        \DB::statement('ALTER TABLE app_tokens DROP PRIMARY KEY');
        \DB::statement('ALTER TABLE app_tokens MODIFY id VARCHAR(36) NOT NULL');
        \DB::statement('ALTER TABLE app_tokens ADD PRIMARY KEY (id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('ALTER TABLE app_tokens DROP PRIMARY KEY');
        \DB::statement('ALTER TABLE app_tokens MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        \DB::statement('ALTER TABLE app_tokens ADD PRIMARY KEY (id)');
    }
};
