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
        // DocumentPathSettings für SupplierContract mit DocumentType-Keys erstellen
        $supplierContractSettings = [
            // Standard-Pfad für SupplierContract (ohne spezifischen DocumentType)
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => null,
                'path_template' => 'vertraege/{supplier_number}/{contract_number}',
                'description' => 'Standard-Pfad für Dokumente zu Vertragsdaten',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            
            // DocumentType-spezifische Pfade für SupplierContract
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'contracts',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/vertragsdokumente',
                'description' => 'Pfad für Vertragsdokumente und Anhänge',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'correspondence',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/korrespondenz',
                'description' => 'Pfad für Korrespondenz zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'invoices',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/{year}',
                'description' => 'Pfad für Abrechnungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name', 'year'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'technical',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/technische-unterlagen',
                'description' => 'Pfad für technische Unterlagen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'certificates',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/zertifikate',
                'description' => 'Pfad für Zertifikate zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'legal_document',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/rechtsdokumente',
                'description' => 'Pfad für Rechtsdokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'photos',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/fotos',
                'description' => 'Pfad für Fotos zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'planning',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/planung',
                'description' => 'Pfad für Planungsdokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'permits',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/genehmigungen',
                'description' => 'Pfad für Genehmigungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'installation',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/installation',
                'description' => 'Pfad für Installationsdokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'maintenance',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/wartung',
                'description' => 'Pfad für Wartungsdokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'formulare',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/formulare',
                'description' => 'Pfad für Formulare zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'information',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/informationen',
                'description' => 'Pfad für Informationsdokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'test_protocol',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/pruefprotokolle',
                'description' => 'Pfad für Prüfprotokolle zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'ordering_material',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/materialbestellungen',
                'description' => 'Pfad für Materialbestellungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'delivery_note',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/lieferscheine',
                'description' => 'Pfad für Lieferscheine zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'commissioning',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/inbetriebnahme',
                'description' => 'Pfad für Inbetriebnahme-Dokumente zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'abr_marktpraemie',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/marktpraemie',
                'description' => 'Pfad für Marktprämie-Abrechnungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'abr_direktvermittlung',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/direktvermittlung',
                'description' => 'Pfad für Direktvermittlung-Abrechnungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'direct_marketing_invoice',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/direktvermarktung',
                'description' => 'Pfad für Direktvermarktung-Rechnungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
        ];

        foreach ($supplierContractSettings as $setting) {
            DocumentPathSetting::updateOrCreate(
                [
                    'documentable_type' => $setting['documentable_type'],
                    'category' => $setting['category'],
                ],
                array_merge($setting, [
                    'is_active' => true,
                    'filename_strategy' => 'original',
                    'preserve_extension' => true,
                    'sanitize_filename' => true,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Entferne alle DocumentPathSettings für SupplierContract
        DocumentPathSetting::where('documentable_type', 'App\Models\SupplierContract')->delete();
    }
};