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
        // DocumentPathSettings nur für die tatsächlich vorhandenen DocumentTypes
        $documentTypeSettings = [
            // ID 1: Planung (planning)
            'planning' => [
                'path' => 'solaranlagen/{plant_number}/planung',
                'description' => 'Planungsdokumente für Solaranlagen'
            ],
            
            // ID 2: Genehmigung (permits)
            'permits' => [
                'path' => 'solaranlagen/{plant_number}/genehmigungen',
                'description' => 'Behördliche Genehmigungen und Bescheide für Solaranlagen'
            ],
            
            // ID 3: Installation (installation)
            'installation' => [
                'path' => 'solaranlagen/{plant_number}/installation',
                'description' => 'Installationsdokumente und Protokolle für Solaranlagen'
            ],
            
            // ID 4: Wartung (maintenance)
            'maintenance' => [
                'path' => 'solaranlagen/{plant_number}/wartung',
                'description' => 'Wartungsprotokolle und Servicedokumente für Solaranlagen'
            ],
            
            // ID 5: Rechnung (invoices)
            'invoices' => [
                'path' => 'solaranlagen/{plant_number}/rechnungen',
                'description' => 'Rechnungen und Abrechnungsdokumente für Solaranlagen'
            ],
            
            // ID 6: Zertifikat (certificates)
            'certificates' => [
                'path' => 'solaranlagen/{plant_number}/zertifikate',
                'description' => 'Zertifikate und Bescheinigungen für Solaranlagen'
            ],
            
            // ID 7: Vertrag (contracts)
            'contracts' => [
                'path' => 'solaranlagen/{plant_number}/vertraege',
                'description' => 'Verträge und rechtliche Dokumente für Solaranlagen'
            ],
            
            // ID 8: Korrespondenz (correspondence)
            'correspondence' => [
                'path' => 'solaranlagen/{plant_number}/korrespondenz',
                'description' => 'E-Mails, Briefe und sonstige Korrespondenz für Solaranlagen'
            ],
            
            // ID 9: Technische Unterlagen (technical)
            'technical' => [
                'path' => 'solaranlagen/{plant_number}/technische-unterlagen',
                'description' => 'Technische Dokumentationen und Datenblätter für Solaranlagen'
            ],
            
            // ID 10: Fotos (photos)
            'photos' => [
                'path' => 'solaranlagen/{plant_number}/fotos',
                'description' => 'Fotos und Bildmaterial für Solaranlagen'
            ],
            
            // ID 11: Marktprämie Abrechnung (abr_marktpraemie)
            'abr_marktpraemie' => [
                'path' => 'solaranlagen/{plant_number}/abrechnungen/marktpraemie',
                'description' => 'Marktprämie Abrechnungen für Solaranlagen'
            ],
            
            // ID 12: Direktvermittlung Abrechnung (abr_direktvermittlung)
            'abr_direktvermittlung' => [
                'path' => 'solaranlagen/{plant_number}/abrechnungen/direktvermittlung',
                'description' => 'Direktvermittlung Abrechnungen für Solaranlagen'
            ],
            
            // ID 13: Prüfprotokoll (test_protocol)
            'test_protocol' => [
                'path' => 'solaranlagen/{plant_number}/pruefprotokolle',
                'description' => 'Prüfprotokolle für Solaranlagen'
            ],
            
            // ID 14: Materialbestellung (ordering_material)
            'ordering_material' => [
                'path' => 'solaranlagen/{plant_number}/materialbestellungen',
                'description' => 'Materialbestellungen für Solaranlagen'
            ],
            
            // ID 15: Lieferschein (delivery_note)
            'delivery_note' => [
                'path' => 'solaranlagen/{plant_number}/lieferscheine',
                'description' => 'Lieferscheine für Solaranlagen'
            ],
            
            // ID 16: Inbetriebnahme (commissioning)
            'commissioning' => [
                'path' => 'solaranlagen/{plant_number}/inbetriebnahme',
                'description' => 'Inbetriebnahme-Dokumente für Solaranlagen'
            ],
            
            // ID 17: Rechtsdokument (legal_document)
            'legal_document' => [
                'path' => 'solaranlagen/{plant_number}/rechtsdokumente',
                'description' => 'Rechtsdokumente für Solaranlagen'
            ],
            
            // ID 18: Formulare (formulare)
            'formulare' => [
                'path' => 'solaranlagen/{plant_number}/formulare',
                'description' => 'Formulare für Solaranlagen'
            ],
            
            // ID 19: Information (information)
            'information' => [
                'path' => 'solaranlagen/{plant_number}/informationen',
                'description' => 'Informationsdokumente für Solaranlagen'
            ],
            
            // ID 20: Direktvermarktung Rechnung (direct_marketing_invoice)
            'direct_marketing_invoice' => [
                'path' => 'solaranlagen/{plant_number}/rechnungen/direktvermarktung',
                'description' => 'Direktvermarktung Rechnungen für Solaranlagen'
            ],
        ];

        foreach ($documentTypeSettings as $category => $config) {
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
        // Entferne nur die DocumentPathSettings für die spezifischen DocumentTypes
        $categoriesToRemove = [
            'planning', 'permits', 'installation', 'maintenance', 'invoices', 'certificates',
            'contracts', 'correspondence', 'technical', 'photos', 'abr_marktpraemie',
            'abr_direktvermittlung', 'test_protocol', 'ordering_material', 'delivery_note',
            'commissioning', 'legal_document', 'formulare', 'information', 'direct_marketing_invoice'
        ];
        
        DocumentPathSetting::where('documentable_type', 'App\Models\SolarPlant')
            ->whereIn('category', $categoriesToRemove)
            ->delete();
    }
};
