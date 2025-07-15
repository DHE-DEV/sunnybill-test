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
        // Fehlende DocumentPathSettings für SupplierContractBilling mit DocumentType-Keys erstellen
        $missingSettings = [
            // Fehlende DocumentType-Keys für SupplierContractBilling
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'planning',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/planung',
                'description' => 'Pfad für Planungsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'permits',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/genehmigungen',
                'description' => 'Pfad für Genehmigungen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'installation',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/installation',
                'description' => 'Pfad für Installationsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'maintenance',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/wartung',
                'description' => 'Pfad für Wartungsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'contracts',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/vertraege',
                'description' => 'Pfad für Vertragsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'photos',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/fotos',
                'description' => 'Pfad für Fotos zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'abr_marktpraemie',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/marktpraemie',
                'description' => 'Pfad für Marktprämie-Abrechnungen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'abr_direktvermittlung',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/direktvermittlung',
                'description' => 'Pfad für Direktvermittlung-Abrechnungen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'test_protocol',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/pruefprotokolle',
                'description' => 'Pfad für Prüfprotokolle zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'ordering_material',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/materialbestellungen',
                'description' => 'Pfad für Materialbestellungen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'delivery_note',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/lieferscheine',
                'description' => 'Pfad für Lieferscheine zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'commissioning',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/inbetriebnahme',
                'description' => 'Pfad für Inbetriebnahme-Dokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'legal_document',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/rechtsdokumente',
                'description' => 'Pfad für Rechtsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'formulare',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/formulare',
                'description' => 'Pfad für Formulare zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'information',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/informationen',
                'description' => 'Pfad für Informationsdokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'direct_marketing_invoice',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/direktvermarktung',
                'description' => 'Pfad für Direktvermarktung-Rechnungen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
        ];

        foreach ($missingSettings as $setting) {
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
        // Entferne die hinzugefügten DocumentPathSettings für SupplierContractBilling
        $categoriesToRemove = [
            'planning', 'permits', 'installation', 'maintenance', 'contracts', 'photos',
            'abr_marktpraemie', 'abr_direktvermittlung', 'test_protocol', 'ordering_material',
            'delivery_note', 'commissioning', 'legal_document', 'formulare', 'information',
            'direct_marketing_invoice'
        ];

        DocumentPathSetting::where('documentable_type', 'App\Models\SupplierContractBilling')
            ->whereIn('category', $categoriesToRemove)
            ->delete();
    }
};