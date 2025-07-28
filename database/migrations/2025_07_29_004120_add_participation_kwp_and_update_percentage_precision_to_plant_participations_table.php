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
        Schema::table('plant_participations', function (Blueprint $table) {
            // Add participation_kwp field
            $table->decimal('participation_kwp', 10, 4)->nullable()->after('percentage');
            
            // Update percentage field to have 4 decimal places
            $table->decimal('percentage', 8, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_participations', function (Blueprint $table) {
            // Remove participation_kwp field
            $table->dropColumn('participation_kwp');
            
            // Revert percentage field back to 2 decimal places
            $table->decimal('percentage', 5, 2)->change();
        });
    }
};
