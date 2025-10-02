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
        Schema::table('articles', function (Blueprint $table) {
            // Make tax_rate nullable since we're transitioning to tax_rate_id
            $table->decimal('tax_rate', 4, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Revert to NOT NULL (note: this may fail if there are NULL values)
            $table->decimal('tax_rate', 4, 2)->nullable(false)->change();
        });
    }
};
