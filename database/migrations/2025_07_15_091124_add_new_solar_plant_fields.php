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
            // Neue Felder hinzufügen
            $table->date('commissioning_date_unit')->nullable()->after('vnb_process_number')->comment('Datum der Inbetriebsetzung');
            $table->string('plot_number')->nullable()->after('location')->comment('Flurstück');
            $table->string('mastr_number_eeg_plant')->nullable()->after('pv_soll_project_number')->comment('MaStR-Nr. der EEG-Anlage');
            $table->date('commissioning_date_eeg_plant')->nullable()->after('mastr_number_eeg_plant')->comment('Inbetriebnahme der EEG-Anlage');
            
            // Bestehende Felder umbenennen
            $table->renameColumn('mastr_number', 'mastr_number_unit');
            $table->renameColumn('mastr_registration_date', 'mastr_registration_date_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            // Umbenannte Felder zurückbenennen
            $table->renameColumn('mastr_number_unit', 'mastr_number');
            $table->renameColumn('mastr_registration_date_unit', 'mastr_registration_date');
            
            // Neue Felder entfernen
            $table->dropColumn([
                'commissioning_date_unit',
                'plot_number',
                'mastr_number_eeg_plant',
                'commissioning_date_eeg_plant'
            ]);
        });
    }
};
