<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractMatchingRule;
use App\Models\SupplierContract;
use App\Models\Supplier;

class ContractMatchingRuleSeeder extends Seeder
{
    public function run()
    {
        // Erstelle zuerst einige Beispiel-Verträge für die Seeder-Daten
        $this->createSampleContracts();

        // Hole alle SupplierContracts für die Regel-Zuordnung
        $contracts = SupplierContract::with('supplier')->get()->keyBy('contract_number');

        $rules = [
            // Wartungsvertrag SolarTech Deutschland
            [
                'contract_number' => 'ST-WARTUNG-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'ST-WARTUNG-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Wartung.*Solaranlage',
                        'match_type' => 'regex',
                        'description' => 'Wartungsvertrag-Erkennung im PDF-Text',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'customer_number',
                        'matching_pattern' => 'KUNDE-001',
                        'match_type' => 'exact',
                        'description' => 'Kundennummer-Zuordnung',
                        'confidence_weight' => 9,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'sender_email',
                        'field_name' => null,
                        'matching_pattern' => 'solartech-deutschland.de',
                        'match_type' => 'contains',
                        'description' => 'E-Mail-Domain-Zuordnung',
                        'confidence_weight' => 7,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],

            // Liefervertrag Energiespeicher Nord
            [
                'contract_number' => 'EN-LIEFER-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'EN-LIEFER-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Batteriespeicher.*Lieferung',
                        'match_type' => 'regex',
                        'description' => 'Batteriespeicher-Liefervertrag im PDF',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'customer_number',
                        'matching_pattern' => 'KUNDE-002',
                        'match_type' => 'exact',
                        'description' => 'Kundennummer-Zuordnung',
                        'confidence_weight' => 9,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Lithium-Ionen',
                        'match_type' => 'contains',
                        'description' => 'Batterietyp-Erkennung',
                        'confidence_weight' => 6,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],

            // Montagevertrag Montage-Profis
            [
                'contract_number' => 'MP-MONTAGE-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'MP-MONTAGE-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Montage.*Solarmodule',
                        'match_type' => 'regex',
                        'description' => 'Montagevertrag-Erkennung im PDF',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'project_number',
                        'matching_pattern' => 'PROJ-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Projektnummer-Zuordnung',
                        'confidence_weight' => 9,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'email_subject',
                        'field_name' => null,
                        'matching_pattern' => 'Montage',
                        'match_type' => 'contains',
                        'description' => 'E-Mail-Betreff-Erkennung',
                        'confidence_weight' => 5,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],

            // Elektroinstallationsvertrag ElektroTechnik Ost
            [
                'contract_number' => 'ETO-ELEKTRO-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'ETO-ELEKTRO-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Elektroinstallation.*Netzanschluss',
                        'match_type' => 'regex',
                        'description' => 'Elektroinstallations-Vertrag im PDF',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'customer_number',
                        'matching_pattern' => 'KUNDE-004',
                        'match_type' => 'exact',
                        'description' => 'Kundennummer-Zuordnung',
                        'confidence_weight' => 9,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Wechselrichter',
                        'match_type' => 'contains',
                        'description' => 'Wechselrichter-Installation',
                        'confidence_weight' => 6,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],

            // Komponentenliefervertrag Green Energy Components
            [
                'contract_number' => 'GEC-KOMPONENTEN-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'GEC-KOMPONENTEN-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Optimierer.*Monitoring',
                        'match_type' => 'regex',
                        'description' => 'Optimierer-Liefervertrag im PDF',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'sender_email',
                        'field_name' => null,
                        'matching_pattern' => 'green-energy-components.co.uk',
                        'match_type' => 'contains',
                        'description' => 'E-Mail-Domain-Zuordnung',
                        'confidence_weight' => 7,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'invoice_number',
                        'matching_pattern' => 'GEC-',
                        'match_type' => 'starts_with',
                        'description' => 'Rechnungsnummer-Präfix-Erkennung',
                        'confidence_weight' => 6,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                ]
            ],

            // Kabelliefervertrag Kabel & Mehr
            [
                'contract_number' => 'KM-KABEL-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'KM-KABEL-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'DC-Kabel.*Steckverbinder',
                        'match_type' => 'regex',
                        'description' => 'Kabel-Liefervertrag im PDF',
                        'confidence_weight' => 8,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'customer_number',
                        'matching_pattern' => 'KUNDE-006',
                        'match_type' => 'exact',
                        'description' => 'Kundennummer-Zuordnung',
                        'confidence_weight' => 9,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Elektromaterial',
                        'match_type' => 'contains',
                        'description' => 'Elektromaterial-Lieferung',
                        'confidence_weight' => 6,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],

            // Energieversorgungsvertrag (allgemein)
            [
                'contract_number' => 'ENERGIE-VERSORGUNG-2024-001',
                'rules' => [
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'contract_number',
                        'matching_pattern' => 'ENERGIE-VERSORGUNG-2024-001',
                        'match_type' => 'exact',
                        'description' => 'Exakte Vertragsnummer-Übereinstimmung',
                        'confidence_weight' => 10,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'market_location_id',
                        'matching_pattern' => '[A-Z0-9]{33}',
                        'match_type' => 'regex',
                        'description' => 'Marktlokations-ID-Format-Erkennung',
                        'confidence_weight' => 9,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'meter_number',
                        'matching_pattern' => 'METER-',
                        'match_type' => 'starts_with',
                        'description' => 'Zählernummer-Präfix-Erkennung',
                        'confidence_weight' => 8,
                        'case_sensitive' => true,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'pdf_text',
                        'field_name' => null,
                        'matching_pattern' => 'Stromlieferung.*kWh',
                        'match_type' => 'regex',
                        'description' => 'Stromliefervertrag-Erkennung',
                        'confidence_weight' => 7,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                    [
                        'field_source' => 'extracted_data',
                        'field_name' => 'creditor_id',
                        'matching_pattern' => '[A-Z0-9]{18}',
                        'match_type' => 'regex',
                        'description' => 'Gläubiger-ID-Format-Erkennung',
                        'confidence_weight' => 6,
                        'case_sensitive' => false,
                        'is_active' => true,
                    ],
                ]
            ],
        ];

        foreach ($rules as $contractRules) {
            $contract = $contracts->get($contractRules['contract_number']);
            
            if (!$contract) {
                continue; // Vertrag nicht gefunden, überspringe
            }

            foreach ($contractRules['rules'] as $ruleData) {
                ContractMatchingRule::create([
                    'supplier_contract_id' => $contract->id,
                    'field_source' => $ruleData['field_source'],
                    'field_name' => $ruleData['field_name'],
                    'matching_pattern' => $ruleData['matching_pattern'],
                    'match_type' => $ruleData['match_type'],
                    'description' => $ruleData['description'],
                    'confidence_weight' => $ruleData['confidence_weight'],
                    'case_sensitive' => $ruleData['case_sensitive'],
                    'is_active' => $ruleData['is_active'],
                ]);
            }
        }
    }

    /**
     * Erstellt Beispiel-Verträge für die Seeder-Daten
     */
    private function createSampleContracts()
    {
        $suppliers = Supplier::all()->keyBy('supplier_number');

        $contracts = [
            [
                'supplier_number' => 'LF-001',
                'contract_number' => 'ST-WARTUNG-2024-001',
                'title' => 'Wartungsvertrag Solaranlagen',
                'description' => 'Jährliche Wartung und Inspektion der Solaranlagen',
                'start_date' => '2024-01-01',
                'end_date' => '2026-12-31',
                'contract_value' => 15000.00,
                'status' => 'active',
                'payment_terms' => 'Jährliche Zahlung im Voraus',
                'contract_recognition_1' => 'ST-WARTUNG',
                'contract_recognition_2' => 'KUNDE-001',
                'contract_recognition_3' => 'Wartung',
                'is_active' => true,
            ],
            [
                'supplier_number' => 'LF-002',
                'contract_number' => 'EN-LIEFER-2024-001',
                'title' => 'Batteriespeicher Liefervertrag',
                'description' => 'Lieferung von Lithium-Ionen Batteriespeichern',
                'start_date' => '2024-02-01',
                'end_date' => '2025-01-31',
                'contract_value' => 85000.00,
                'status' => 'active',
                'payment_terms' => 'Zahlung bei Lieferung',
                'contract_recognition_1' => 'EN-LIEFER',
                'contract_recognition_2' => 'KUNDE-002',
                'contract_recognition_3' => 'Batteriespeicher',
                'is_active' => true,
            ],
            [
                'supplier_number' => 'LF-003',
                'contract_number' => 'MP-MONTAGE-2024-001',
                'title' => 'Montagevertrag Solarmodule',
                'description' => 'Montage und Installation von Solarmodulen auf Dächern',
                'start_date' => '2024-03-01',
                'end_date' => '2024-12-31',
                'contract_value' => 45000.00,
                'status' => 'active',
                'payment_terms' => 'Zahlung nach Abnahme',
                'contract_recognition_1' => 'MP-MONTAGE',
                'contract_recognition_2' => 'PROJ-2024-001',
                'contract_recognition_3' => 'Montage',
                'is_active' => true,
            ],
            [
                'supplier_number' => 'LF-004',
                'contract_number' => 'ETO-ELEKTRO-2024-001',
                'title' => 'Elektroinstallationsvertrag',
                'description' => 'Elektrische Installation und Netzanschluss',
                'start_date' => '2024-04-01',
                'end_date' => '2024-11-30',
                'contract_value' => 25000.00,
                'status' => 'active',
                'payment_terms' => 'Zahlung in 3 Raten',
                'contract_recognition_1' => 'ETO-ELEKTRO',
                'contract_recognition_2' => 'KUNDE-004',
                'contract_recognition_3' => 'Elektroinstallation',
                'is_active' => true,
            ],
            [
                'supplier_number' => 'LF-005',
                'contract_number' => 'GEC-KOMPONENTEN-2024-001',
                'title' => 'Optimierer und Monitoring Liefervertrag',
                'description' => 'Lieferung von Leistungsoptimierern und Monitoring-Systemen',
                'start_date' => '2024-05-01',
                'end_date' => '2025-04-30',
                'contract_value' => 35000.00,
                'status' => 'active',
                'payment_terms' => 'Zahlung innerhalb 30 Tage',
                'contract_recognition_1' => 'GEC-KOMPONENTEN',
                'contract_recognition_2' => 'Optimierer',
                'contract_recognition_3' => 'Monitoring',
                'is_active' => true,
            ],
            [
                'supplier_number' => 'LF-006',
                'contract_number' => 'KM-KABEL-2024-001',
                'title' => 'Kabel und Elektromaterial Liefervertrag',
                'description' => 'Lieferung von DC-Kabeln und Steckverbindern',
                'start_date' => '2024-06-01',
                'end_date' => '2025-05-31',
                'contract_value' => 18000.00,
                'status' => 'active',
                'payment_terms' => 'Zahlung bei Lieferung',
                'contract_recognition_1' => 'KM-KABEL',
                'contract_recognition_2' => 'KUNDE-006',
                'contract_recognition_3' => 'DC-Kabel',
                'is_active' => true,
            ],
            [
                'supplier_number' => null, // Allgemeiner Energieversorgungsvertrag
                'contract_number' => 'ENERGIE-VERSORGUNG-2024-001',
                'title' => 'Stromliefervertrag',
                'description' => 'Stromlieferung für Solaranlagen-Betrieb',
                'start_date' => '2024-01-01',
                'end_date' => '2025-12-31',
                'contract_value' => 12000.00,
                'status' => 'active',
                'payment_terms' => 'Monatliche Abschlagszahlungen',
                'contract_recognition_1' => 'ENERGIE-VERSORGUNG',
                'contract_recognition_2' => 'METER-12345',
                'contract_recognition_3' => 'Stromlieferung',
                'is_active' => true,
            ],
        ];

        foreach ($contracts as $contractData) {
            $supplier = null;
            if ($contractData['supplier_number']) {
                $supplier = $suppliers->get($contractData['supplier_number']);
                if (!$supplier) {
                    continue; // Supplier nicht gefunden, überspringe
                }
            }

            // Prüfe ob Vertrag bereits existiert
            $existingContract = SupplierContract::where('contract_number', $contractData['contract_number'])->first();
            if ($existingContract) {
                continue; // Vertrag existiert bereits
            }

            SupplierContract::create([
                'supplier_id' => $supplier ? $supplier->id : null,
                'contract_number' => $contractData['contract_number'],
                'title' => $contractData['title'],
                'description' => $contractData['description'],
                'start_date' => $contractData['start_date'],
                'end_date' => $contractData['end_date'],
                'contract_value' => $contractData['contract_value'],
                'currency' => 'EUR',
                'status' => $contractData['status'],
                'payment_terms' => $contractData['payment_terms'],
                'contract_recognition_1' => $contractData['contract_recognition_1'],
                'contract_recognition_2' => $contractData['contract_recognition_2'],
                'contract_recognition_3' => $contractData['contract_recognition_3'],
                'is_active' => $contractData['is_active'],
                'created_by' => 'Seeder',
            ]);
        }
    }
}