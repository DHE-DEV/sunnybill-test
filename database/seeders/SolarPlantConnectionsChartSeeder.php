<?php

namespace Database\Seeders;

use App\Models\MermaidChart;
use Illuminate\Database\Seeder;

class SolarPlantConnectionsChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chart erstellen oder aktualisieren
        MermaidChart::updateOrCreate(
            ['name' => 'Solaranlagen-Verknüpfungen und Abrechnungsstrukturen'],
            [
                'description' => 'Zeigt die Verknüpfungen zwischen Solaranlagen, Lieferanten, Verträgen, Investoren und die prozentuale Aufteilung von Abrechnungen.',
                'template' => MermaidChart::getDefaultSolarPlantConnectionsTemplate(),
                'chart_type' => 'solar_plant_connections',
                'is_active' => true,
            ]
        );
    }
}