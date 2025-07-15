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
        // Erstelle DocumentPathSetting für SolarPlant falls nicht vorhanden
        DocumentPathSetting::updateOrCreate(
            [
                'documentable_type' => 'App\Models\SolarPlant',
                'category' => null,
            ],
            [
                'path_template' => 'solaranlagen/{plant_number}',
                'description' => 'Standard-Pfad für Solaranlagen-Dokumente',
                'placeholders' => ['plant_number', 'plant_name', 'plant_id'],
                'is_active' => true,
                'filename_strategy' => 'original',
                'preserve_extension' => true,
                'sanitize_filename' => true,
            ]
        );

        // Zusätzliche kategorie-spezifische Pfade für SolarPlant
        $categories = [
            'planning' => [
                'path' => 'solaranlagen/{plant_number}/planung',
                'description' => 'Planungsdokumente für Solaranlagen'
            ],
            'permits' => [
                'path' => 'solaranlagen/{plant_number}/genehmigungen',
                'description' => 'Genehmigungsdokumente für Solaranlagen'
            ],
            'installation' => [
                'path' => 'solaranlagen/{plant_number}/installation',
                'description' => 'Installationsdokumente für Solaranlagen'
            ],
            'maintenance' => [
                'path' => 'solaranlagen/{plant_number}/wartung',
                'description' => 'Wartungsdokumente für Solaranlagen'
            ],
            'certificates' => [
                'path' => 'solaranlagen/{plant_number}/zertifikate',
                'description' => 'Zertifikate für Solaranlagen'
            ],
            'contracts' => [
                'path' => 'solaranlagen/{plant_number}/vertraege',
                'description' => 'Vertragsdokumente für Solaranlagen'
            ],
            'correspondence' => [
                'path' => 'solaranlagen/{plant_number}/korrespondenz',
                'description' => 'Korrespondenz für Solaranlagen'
            ],
            'technical' => [
                'path' => 'solaranlagen/{plant_number}/technische-unterlagen',
                'description' => 'Technische Unterlagen für Solaranlagen'
            ],
            'photos' => [
                'path' => 'solaranlagen/{plant_number}/fotos',
                'description' => 'Fotos für Solaranlagen'
            ],
        ];

        foreach ($categories as $category => $config) {
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
        // Entferne alle SolarPlant DocumentPathSettings
        DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')->delete();
    }
};