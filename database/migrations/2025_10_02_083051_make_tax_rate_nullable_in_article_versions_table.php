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
        Schema::table('article_versions', function (Blueprint $table) {
            // Make tax_rate nullable since articles now use tax_rate_id
            $table->decimal('tax_rate', 5, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_versions', function (Blueprint $table) {
            // Revert to NOT NULL (note: this may fail if there are NULL values)
            $table->decimal('tax_rate', 5, 4)->nullable(false)->default(0.19)->change();
        });
    }
};
