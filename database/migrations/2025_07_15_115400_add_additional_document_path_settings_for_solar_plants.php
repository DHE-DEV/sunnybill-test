<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DocumentPathSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zusätzliche kategorie-spezifische Pfade für häufig verwendete DocumentTypes
        $additionalCategories = [
            'formulare' => [
                'path' => 'solaranlagen/{plant_number}/formulare',
                'description' => 'Formulare für Solaranlagen'
            ],
            'forms' => [
                'path' => 'solaranlagen/{plant_number}/formulare',
                'description' => 'Formulare für Solaranlagen (EN)'
            ],
            'antraege' => [
                'path' => 'solaranlagen/{plant_number}/antraege',
                'description' => 'Anträge für Solaranlagen'
            ],
            'applications' => [
                'path' => 'solaranlagen/{plant_number}/antraege',
                'description' => 'Anträge für Solaranlagen (EN)'
            ],
            'berichte' => [
                'path' => 'solaranlagen/{plant_number}/berichte',
                'description' => 'Berichte für Solaranlagen'
            ],
            'reports' => [
                'path' => 'solaranlagen/{plant_number}/berichte',
                'description' => 'Berichte für Solaranlagen (EN)'
            ],
            'protokolle' => [
                'path' => 'solaranlagen/{plant_number}/protokolle',
                'description' => 'Protokolle für Solaranlagen'
            ],
            'protocols' => [
                'path' => 'solaranlagen/{plant_number}/protokolle',
                'description' => 'Protokolle für Solaranlagen (EN)'
            ],
            'messungen' => [
                'path' => 'solaranlagen/{plant_number}/messungen',
                'description' => 'Messungen für Solaranlagen'
            ],
            'measurements' => [
                'path' => 'solaranlagen/{plant_number}/messungen',
                'description' => 'Messungen für Solaranlagen (EN)'
            ],
            'dokumentation' => [
                'path' => 'solaranlagen/{plant_number}/dokumentation',
                'description' => 'Dokumentation für Solaranlagen'
            ],
            'documentation' => [
                'path' => 'solaranlagen/{plant_number}/dokumentation',
                'description' => 'Dokumentation für Solaranlagen (EN)'
            ],
            'pruefungen' => [
                'path' => 'solaranlagen/{plant_number}/pruefungen',
                'description' => 'Prüfungen für Solaranlagen'
            ],
            'inspections' => [
                'path' => 'solaranlagen/{plant_number}/pruefungen',
                'description' => 'Prüfungen für Solaranlagen (EN)'
            ],
        ];

        foreach ($additionalCategories as $category => $config) {
            DocumentPathSetting::updateOrCreate(
                [
                    'documentable_type' => 'App\Models\SolarPlant',
                    'category' => $category,
                ],
                [
                    'path_template' => $config['path'],
                    'description' => $config['description'],
                    'placeholders' => ['plant_number', 'plant_name', 'plant_id'],
                    'is_active' => true,
                    'filename_strategy' => 'original',
                    'preserve_extension' => true,
                    'sanitize_filename' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Entferne die zusätzlichen SolarPlant DocumentPathSettings
        $categoriesToRemove = [
            'formulare', 'forms', 'antraege', 'applications', 'berichte', 'reports',
            'protokolle', 'protocols', 'messungen', 'measurements', 'dokumentation',
            'documentation', 'pruefungen', 'inspections'
        ];
        
        DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')
            ->whereIn('category', $categoriesToRemove)
            ->delete();
    }
};