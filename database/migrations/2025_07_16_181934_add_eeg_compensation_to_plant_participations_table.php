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
            $table->decimal('eeg_compensation_per_kwh', 8, 6)->nullable()->after('percentage')
                ->comment('Vertraglich zugesicherte EEG-Vergütung pro kWh in EUR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_participations', function (Blueprint $table) {
            $table->dropColumn('eeg_compensation_per_kwh');
        });
    }
};
