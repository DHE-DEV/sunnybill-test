<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FieldConfig;

class FieldConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supplier Contract Felder
        $this->seedSupplierContractFields();
        
        // Customer Felder (Beispiel)
        $this->seedCustomerFields();
        
        // Supplier Felder (Beispiel)
        $this->seedSupplierFields();
        
        // Solar Plant Felder (Beispiel)
        $this->seedSolarPlantFields();
    }

    /**
     * Seeder für alle SupplierContract Felder
     */
    private function seedSupplierContractFields(): void
    {
        $fields = [
            // Section: Vertragsdaten
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'title',
                'field_label' => 'Titel',
                'field_description' => 'Beliebiger Kurztext zur Erkennung in Listen',
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'unique' => false,
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 1,
                'column_span' => 2,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'description',
                'field_label' => 'Beschreibung',
                'field_description' => null,
                'field_type' => 'textarea',
                'field_options' => [
                    'rows' => 3,
                    'max_length' => 1000,
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 2,
                'column_span' => 2,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'supplier_id',
                'field_label' => 'Lieferant',
                'field_description' => null,
                'field_type' => 'select',
                'field_options' => [
                    'searchable' => true,
                    'preload' => true,
                    'relationship' => 'supplier',
                    'display_field' => 'company_name',
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 3,
                'column_span' => 1,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'status',
                'field_label' => 'Status',
                'field_description' => null,
                'field_type' => 'select',
                'field_options' => [
                    'options' => [
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                    ],
                    'default' => 'draft',
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 4,
                'column_span' => 1,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'contract_number',
                'field_label' => 'Vertragsnummer intern',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'unique' => true,
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 5,
                'column_span' => 1,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'creditor_number',
                'field_label' => 'Eigene Kundennummer bei Lieferant',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'placeholder' => 'z.B. KR-12345',
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 6,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'external_contract_number',
                'field_label' => 'Vertragsnummer extern',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'placeholder' => 'z.B. EXT-2024-001',
                ],
                'section_name' => 'Vertragsdaten',
                'section_sort_order' => 1,
                'sort_order' => 7,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],

            // Section: Laufzeit & Wert
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'start_date',
                'field_label' => 'Startdatum',
                'field_description' => null,
                'field_type' => 'date',
                'field_options' => [],
                'section_name' => 'Laufzeit & Wert',
                'section_sort_order' => 2,
                'sort_order' => 1,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'end_date',
                'field_label' => 'Enddatum',
                'field_description' => null,
                'field_type' => 'date',
                'field_options' => [],
                'section_name' => 'Laufzeit & Wert',
                'section_sort_order' => 2,
                'sort_order' => 2,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'contract_value',
                'field_label' => 'Vertragswert',
                'field_description' => null,
                'field_type' => 'number',
                'field_options' => [
                    'step' => 0.01,
                    'prefix' => '€',
                ],
                'section_name' => 'Laufzeit & Wert',
                'section_sort_order' => 2,
                'sort_order' => 3,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'currency',
                'field_label' => 'Währung',
                'field_description' => null,
                'field_type' => 'select',
                'field_options' => [
                    'options' => [
                        'EUR' => 'Euro (EUR)',
                        'USD' => 'US-Dollar (USD)',
                        'CHF' => 'Schweizer Franken (CHF)',
                    ],
                    'default' => 'EUR',
                ],
                'section_name' => 'Laufzeit & Wert',
                'section_sort_order' => 2,
                'sort_order' => 4,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],

            // Section: Vertragserkennung
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'contract_recognition_1',
                'field_label' => 'Vertragserkennung 1',
                'field_description' => 'Diese Informationen werden zur automatischen Vertragserkennung benötigt. Es müssen nicht alle Felder befüllt werden.',
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'placeholder' => 'z.B. Erkennungsmerkmal 1',
                ],
                'section_name' => 'Vertragserkennung',
                'section_sort_order' => 3,
                'sort_order' => 1,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'contract_recognition_2',
                'field_label' => 'Vertragserkennung 2',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'placeholder' => 'z.B. Erkennungsmerkmal 2',
                ],
                'section_name' => 'Vertragserkennung',
                'section_sort_order' => 3,
                'sort_order' => 2,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'contract_recognition_3',
                'field_label' => 'Vertragserkennung 3',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => [
                    'max_length' => 255,
                    'placeholder' => 'z.B. Erkennungsmerkmal 3',
                ],
                'section_name' => 'Vertragserkennung',
                'section_sort_order' => 3,
                'sort_order' => 3,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],

            // Section: Zusätzliche Informationen
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'payment_terms',
                'field_label' => 'Zahlungsbedingungen',
                'field_description' => null,
                'field_type' => 'textarea',
                'field_options' => [
                    'rows' => 3,
                ],
                'section_name' => 'Zusätzliche Informationen',
                'section_sort_order' => 4,
                'sort_order' => 1,
                'column_span' => 2,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'notes',
                'field_label' => 'Notizen',
                'field_description' => null,
                'field_type' => 'textarea',
                'field_options' => [
                    'rows' => 3,
                ],
                'section_name' => 'Zusätzliche Informationen',
                'section_sort_order' => 4,
                'sort_order' => 2,
                'column_span' => 2,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'supplier_contract',
                'field_key' => 'is_active',
                'field_label' => 'Aktiv',
                'field_description' => null,
                'field_type' => 'toggle',
                'field_options' => [
                    'default' => true,
                ],
                'section_name' => 'Zusätzliche Informationen',
                'section_sort_order' => 4,
                'sort_order' => 3,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
        ];

        foreach ($fields as $field) {
            FieldConfig::updateOrCreate([
                'entity_type' => $field['entity_type'],
                'field_key' => $field['field_key'],
            ], $field);
        }

        // Migriere bestehende DummyFieldConfig Daten
        FieldConfig::migrateDummyFieldConfigs();
    }

    /**
     * Seeder für Customer Felder (Beispiel)
     */
    private function seedCustomerFields(): void
    {
        $fields = [
            [
                'entity_type' => 'customer',
                'field_key' => 'company_name',
                'field_label' => 'Firmenname',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => ['max_length' => 255],
                'section_name' => 'Grunddaten',
                'section_sort_order' => 1,
                'sort_order' => 1,
                'column_span' => 2,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
            [
                'entity_type' => 'customer',
                'field_key' => 'email',
                'field_label' => 'E-Mail',
                'field_description' => null,
                'field_type' => 'email',
                'field_options' => ['max_length' => 255],
                'section_name' => 'Kontaktdaten',
                'section_sort_order' => 2,
                'sort_order' => 1,
                'column_span' => 1,
                'is_required' => false,
                'is_active' => true,
                'is_system_field' => true,
            ],
        ];

        foreach ($fields as $field) {
            FieldConfig::updateOrCreate([
                'entity_type' => $field['entity_type'],
                'field_key' => $field['field_key'],
            ], $field);
        }
    }

    /**
     * Seeder für Supplier Felder (Beispiel)
     */
    private function seedSupplierFields(): void
    {
        $fields = [
            [
                'entity_type' => 'supplier',
                'field_key' => 'company_name',
                'field_label' => 'Firmenname',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => ['max_length' => 255],
                'section_name' => 'Grunddaten',
                'section_sort_order' => 1,
                'sort_order' => 1,
                'column_span' => 2,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
        ];

        foreach ($fields as $field) {
            FieldConfig::updateOrCreate([
                'entity_type' => $field['entity_type'],
                'field_key' => $field['field_key'],
            ], $field);
        }
    }

    /**
     * Seeder für Solar Plant Felder (Beispiel)
     */
    private function seedSolarPlantFields(): void
    {
        $fields = [
            [
                'entity_type' => 'solar_plant',
                'field_key' => 'plant_number',
                'field_label' => 'Anlagennummer',
                'field_description' => null,
                'field_type' => 'text',
                'field_options' => ['max_length' => 255],
                'section_name' => 'Anlagendaten',
                'section_sort_order' => 1,
                'sort_order' => 1,
                'column_span' => 1,
                'is_required' => true,
                'is_active' => true,
                'is_system_field' => true,
            ],
        ];

        foreach ($fields as $field) {
            FieldConfig::updateOrCreate([
                'entity_type' => $field['entity_type'],
                'field_key' => $field['field_key'],
            ], $field);
        }
    }
}