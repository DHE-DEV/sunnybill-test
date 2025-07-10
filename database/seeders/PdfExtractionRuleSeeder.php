<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PdfExtractionRule;
use App\Models\Supplier;

class PdfExtractionRuleSeeder extends Seeder
{
    public function run()
    {
        // Hole alle Supplier für die Regel-Zuordnung
        $suppliers = Supplier::all()->keyBy('supplier_number');

        $rules = [
            // Allgemeine Regeln für alle Supplier (supplier_id = null)
            [
                'supplier_number' => null,
                'rules' => [
                    // Rechnungsnummer
                    [
                        'field_name' => 'invoice_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Rechnung(?:snummer)?[:\s]+([A-Z0-9\-\/]+)',
                        'fallback_pattern' => 'RE[:\-\s]*([0-9\-\/]+)',
                        'description' => 'Extraktion der Rechnungsnummer',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],
                    [
                        'field_name' => 'invoice_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Invoice[:\s]+([A-Z0-9\-\/]+)',
                        'fallback_pattern' => 'INV[:\-\s]*([0-9\-\/]+)',
                        'description' => 'Extraktion der Rechnungsnummer (englisch)',
                        'priority' => 9,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Rechnungsdatum
                    [
                        'field_name' => 'invoice_date',
                        'extraction_method' => 'regex',
                        'pattern' => 'Rechnung(?:sdatum)?[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'fallback_pattern' => 'Datum[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'description' => 'Extraktion des Rechnungsdatums',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],
                    [
                        'field_name' => 'invoice_date',
                        'extraction_method' => 'regex',
                        'pattern' => 'Invoice Date[:\s]+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})',
                        'fallback_pattern' => 'Date[:\s]+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})',
                        'description' => 'Extraktion des Rechnungsdatums (englisch)',
                        'priority' => 9,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Fälligkeitsdatum
                    [
                        'field_name' => 'due_date',
                        'extraction_method' => 'regex',
                        'pattern' => 'Fällig(?:keitsdatum)?[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'fallback_pattern' => 'Zahlbar bis[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'description' => 'Extraktion des Fälligkeitsdatums',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Gesamtbetrag
                    [
                        'field_name' => 'total_amount',
                        'extraction_method' => 'regex',
                        'pattern' => 'Gesamt(?:betrag)?[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'fallback_pattern' => 'Total[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'description' => 'Extraktion des Gesamtbetrags',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],
                    [
                        'field_name' => 'total_amount',
                        'extraction_method' => 'regex',
                        'pattern' => 'Rechnungsbetrag[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'fallback_pattern' => 'Betrag[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'description' => 'Extraktion des Rechnungsbetrags',
                        'priority' => 9,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Nettobetrag
                    [
                        'field_name' => 'net_amount',
                        'extraction_method' => 'regex',
                        'pattern' => 'Netto(?:betrag)?[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'fallback_pattern' => 'Net[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'description' => 'Extraktion des Nettobetrags',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Steuerbetrag
                    [
                        'field_name' => 'tax_amount',
                        'extraction_method' => 'regex',
                        'pattern' => 'MwSt\.?\s*(?:\d+%)?[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'fallback_pattern' => 'Steuer[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'description' => 'Extraktion des Steuerbetrags',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Steuersatz
                    [
                        'field_name' => 'tax_rate',
                        'extraction_method' => 'regex',
                        'pattern' => 'MwSt\.?\s*(\d+(?:[,\.]\d+)?)\s*%',
                        'fallback_pattern' => 'Steuersatz[:\s]+(\d+(?:[,\.]\d+)?)\s*%',
                        'description' => 'Extraktion des Steuersatzes',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Kundennummer
                    [
                        'field_name' => 'customer_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Kunden(?:nummer)?[:\s]+([A-Z0-9\-\/]+)',
                        'fallback_pattern' => 'Customer[:\s]+([A-Z0-9\-\/]+)',
                        'description' => 'Extraktion der Kundennummer',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Vertragsnummer
                    [
                        'field_name' => 'contract_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Vertrag(?:snummer)?[:\s]+([A-Z0-9\-\/]+)',
                        'fallback_pattern' => 'Contract[:\s]+([A-Z0-9\-\/]+)',
                        'description' => 'Extraktion der Vertragsnummer',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Abrechnungszeitraum Start
                    [
                        'field_name' => 'period_start',
                        'extraction_method' => 'regex',
                        'pattern' => 'Abrechnungszeitraum[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})\s*(?:bis|\-)',
                        'fallback_pattern' => 'Zeitraum[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})\s*(?:bis|\-)',
                        'description' => 'Extraktion Abrechnungszeitraum Start',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Abrechnungszeitraum Ende
                    [
                        'field_name' => 'period_end',
                        'extraction_method' => 'regex',
                        'pattern' => 'Abrechnungszeitraum[:\s]+\d{1,2}\.?\d{1,2}\.?\d{2,4}\s*(?:bis|\-)\s*(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'fallback_pattern' => 'bis[:\s]+(\d{1,2}\.?\d{1,2}\.?\d{2,4})',
                        'description' => 'Extraktion Abrechnungszeitraum Ende',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],
                ]
            ],

            // Energieversorger-spezifische Regeln
            [
                'supplier_number' => null,
                'rules' => [
                    // Marktlokations-ID
                    [
                        'field_name' => 'market_location_id',
                        'extraction_method' => 'regex',
                        'pattern' => 'Marktlokations?-?ID[:\s]+([A-Z0-9]{33})',
                        'fallback_pattern' => 'MaLo[:\s]+([A-Z0-9]{33})',
                        'description' => 'Extraktion der Marktlokations-ID',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Zählernummer
                    [
                        'field_name' => 'meter_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Zähler(?:nummer)?[:\s]+([A-Z0-9\-]+)',
                        'fallback_pattern' => 'Meter[:\s]+([A-Z0-9\-]+)',
                        'description' => 'Extraktion der Zählernummer',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Netzbetreiber
                    [
                        'field_name' => 'grid_operator',
                        'extraction_method' => 'keyword_search',
                        'pattern' => 'Netzbetreiber:',
                        'fallback_pattern' => 'Grid Operator:',
                        'description' => 'Extraktion des Netzbetreibers',
                        'priority' => 8,
                        'options' => ['case_sensitive' => false],
                        'is_active' => true,
                    ],

                    // Verbrauch in kWh
                    [
                        'field_name' => 'consumption_kwh',
                        'extraction_method' => 'regex',
                        'pattern' => 'Verbrauch[:\s]+(\d+(?:[,\.]\d+)?)\s*kWh',
                        'fallback_pattern' => 'Consumption[:\s]+(\d+(?:[,\.]\d+)?)\s*kWh',
                        'description' => 'Extraktion des Verbrauchs in kWh',
                        'priority' => 9,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Grundpreis
                    [
                        'field_name' => 'base_price',
                        'extraction_method' => 'regex',
                        'pattern' => 'Grundpreis[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'fallback_pattern' => 'Base Price[:\s]+(\d+[,\.]\d{2})\s*€?',
                        'description' => 'Extraktion des Grundpreises',
                        'priority' => 8,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Arbeitspreis
                    [
                        'field_name' => 'work_price',
                        'extraction_method' => 'regex',
                        'pattern' => 'Arbeitspreis[:\s]+(\d+[,\.]\d{2,4})\s*(?:€|ct)\/kWh',
                        'fallback_pattern' => 'Work Price[:\s]+(\d+[,\.]\d{2,4})\s*(?:€|ct)\/kWh',
                        'description' => 'Extraktion des Arbeitspreises',
                        'priority' => 8,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Zählerstand Alt
                    [
                        'field_name' => 'meter_reading_old',
                        'extraction_method' => 'regex',
                        'pattern' => 'Zählerstand\s+(?:alt|vorher)[:\s]+(\d+(?:[,\.]\d+)?)',
                        'fallback_pattern' => 'Previous Reading[:\s]+(\d+(?:[,\.]\d+)?)',
                        'description' => 'Extraktion des alten Zählerstands',
                        'priority' => 7,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Zählerstand Neu
                    [
                        'field_name' => 'meter_reading_new',
                        'extraction_method' => 'regex',
                        'pattern' => 'Zählerstand\s+(?:neu|aktuell)[:\s]+(\d+(?:[,\.]\d+)?)',
                        'fallback_pattern' => 'Current Reading[:\s]+(\d+(?:[,\.]\d+)?)',
                        'description' => 'Extraktion des neuen Zählerstands',
                        'priority' => 7,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Gläubiger-ID
                    [
                        'field_name' => 'creditor_id',
                        'extraction_method' => 'regex',
                        'pattern' => 'Gläubiger-?ID[:\s]+([A-Z0-9]{18})',
                        'fallback_pattern' => 'Creditor ID[:\s]+([A-Z0-9]{18})',
                        'description' => 'Extraktion der Gläubiger-ID',
                        'priority' => 8,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],

                    // Mandatsreferenz
                    [
                        'field_name' => 'mandate_reference',
                        'extraction_method' => 'regex',
                        'pattern' => 'Mandatsreferenz[:\s]+([A-Z0-9\-\/]+)',
                        'fallback_pattern' => 'Mandate Reference[:\s]+([A-Z0-9\-\/]+)',
                        'description' => 'Extraktion der Mandatsreferenz',
                        'priority' => 7,
                        'options' => ['case_sensitive' => false, 'multiline' => true],
                        'is_active' => true,
                    ],
                ]
            ],

            // SolarTech Deutschland GmbH spezifische Regeln (LF-001)
            [
                'supplier_number' => 'LF-001',
                'rules' => [
                    [
                        'field_name' => 'invoice_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'ST-\d{4}-\d{6}',
                        'fallback_pattern' => 'SolarTech[:\s]+([A-Z0-9\-]+)',
                        'description' => 'SolarTech spezifisches Rechnungsformat',
                        'priority' => 15,
                        'options' => ['case_sensitive' => true],
                        'is_active' => true,
                    ],
                    [
                        'field_name' => 'project_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'Projekt[:\s]+([A-Z0-9\-]+)',
                        'fallback_pattern' => 'Project[:\s]+([A-Z0-9\-]+)',
                        'description' => 'SolarTech Projektnummer',
                        'priority' => 10,
                        'options' => ['case_sensitive' => false],
                        'is_active' => true,
                    ],
                ]
            ],

            // Energiespeicher Nord AG spezifische Regeln (LF-002)
            [
                'supplier_number' => 'LF-002',
                'rules' => [
                    [
                        'field_name' => 'invoice_number',
                        'extraction_method' => 'regex',
                        'pattern' => 'EN-\d{4}-\d{6}',
                        'fallback_pattern' => 'EnergieSpeicher[:\s]+([A-Z0-9\-]+)',
                        'description' => 'Energiespeicher Nord spezifisches Format',
                        'priority' => 15,
                        'options' => ['case_sensitive' => true],
                        'is_active' => true,
                    ],
                    [
                        'field_name' => 'battery_type',
                        'extraction_method' => 'keyword_search',
                        'pattern' => 'Batterietyp:',
                        'fallback_pattern' => 'Battery Type:',
                        'description' => 'Batterietyp-Extraktion',
                        'priority' => 8,
                        'options' => ['case_sensitive' => false],
                        'is_active' => true,
                    ],
                ]
            ],
        ];

        foreach ($rules as $supplierRules) {
            $supplier = null;
            
            if ($supplierRules['supplier_number']) {
                $supplier = $suppliers->get($supplierRules['supplier_number']);
                if (!$supplier) {
                    continue; // Supplier nicht gefunden, überspringe
                }
            }

            foreach ($supplierRules['rules'] as $ruleData) {
                PdfExtractionRule::create([
                    'supplier_id' => $supplier ? $supplier->id : null,
                    'field_name' => $ruleData['field_name'],
                    'extraction_method' => $ruleData['extraction_method'],
                    'pattern' => $ruleData['pattern'],
                    'fallback_pattern' => $ruleData['fallback_pattern'] ?? null,
                    'description' => $ruleData['description'],
                    'priority' => $ruleData['priority'],
                    'options' => $ruleData['options'] ?? [],
                    'is_active' => $ruleData['is_active'],
                ]);
            }
        }
    }
}