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
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->string('mastr_number')->nullable()->after('location');
            $table->date('mastr_registration_date')->nullable()->after('mastr_number');
            $table->string('malo_id')->nullable()->after('mastr_registration_date');
            $table->string('melo_id')->nullable()->after('malo_id');
            $table->string('vnb_process_number')->nullable()->after('melo_id');
            $table->date('unit_commissioning_date')->nullable()->after('vnb_process_number');
            $table->date('pv_soll_planning_date')->nullable()->after('unit_commissioning_date');
            $table->string('pv_soll_project_number')->nullable()->after('pv_soll_planning_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn([
                'mastr_number',
                'mastr_registration_date',
                'malo_id',
                'melo_id',
                'vnb_process_number',
                'unit_commissioning_date',
                'pv_soll_planning_date',
                'pv_soll_project_number',
            ]);
        });
    }
};
