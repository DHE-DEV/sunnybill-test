<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplierRecognitionPattern;
use App\Models\Supplier;

class SupplierRecognitionPatternSeeder extends Seeder
{
    public function run()
    {
        // Hole alle Supplier für die Pattern-Zuordnung
        $suppliers = Supplier::all()->keyBy('supplier_number');

        $patterns = [
            // SolarTech Deutschland GmbH (LF-001)
            [
                'supplier_number' => 'LF-001',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'solartech-deutschland.de',
                        'description' => 'E-Mail Domain von SolarTech Deutschland',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'SolarTech Deutschland GmbH',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'DE123456789',
                        'description' => 'USt-ID von SolarTech Deutschland',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'bank_account',
                        'pattern_value' => 'DE89700800000123456789',
                        'description' => 'IBAN von SolarTech Deutschland',
                        'confidence_weight' => 8,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Solarstraße 25',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                ]
            ],

            // Energiespeicher Nord AG (LF-002)
            [
                'supplier_number' => 'LF-002',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'energiespeicher-nord.de',
                        'description' => 'E-Mail Domain von Energiespeicher Nord',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'Energiespeicher Nord AG',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'DE987654321',
                        'description' => 'USt-ID von Energiespeicher Nord',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Batterieweg 12',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Lithium-Ionen',
                        'description' => 'Spezialisierung auf Batteriespeicher',
                        'confidence_weight' => 6,
                        'is_active' => true,
                    ],
                ]
            ],

            // Montage-Profis Rheinland GmbH (LF-003)
            [
                'supplier_number' => 'LF-003',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'montage-profis.de',
                        'description' => 'E-Mail Domain von Montage-Profis',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'Montage-Profis Rheinland GmbH',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'DE456789012',
                        'description' => 'USt-ID von Montage-Profis',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Handwerkerstraße 89',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Montage',
                        'description' => 'Dienstleistungsbereich',
                        'confidence_weight' => 5,
                        'is_active' => true,
                    ],
                ]
            ],

            // ElektroTechnik Ost GmbH (LF-004)
            [
                'supplier_number' => 'LF-004',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'elektrotechnik-ost.de',
                        'description' => 'E-Mail Domain von ElektroTechnik Ost',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'ElektroTechnik Ost GmbH',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'DE789012345',
                        'description' => 'USt-ID von ElektroTechnik Ost',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Stromstraße 45',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Netzanschluss',
                        'description' => 'Spezialisierung auf Elektroinstallation',
                        'confidence_weight' => 6,
                        'is_active' => true,
                    ],
                ]
            ],

            // Green Energy Components Ltd. (LF-005)
            [
                'supplier_number' => 'LF-005',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'green-energy-components.co.uk',
                        'description' => 'E-Mail Domain von Green Energy Components',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'Green Energy Components Ltd.',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'GB123456789',
                        'description' => 'VAT-ID von Green Energy Components',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => '123 Solar Avenue',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Optimierer',
                        'description' => 'Spezialisierung auf Optimierer',
                        'confidence_weight' => 6,
                        'is_active' => true,
                    ],
                ]
            ],

            // Kabel & Mehr Handels GmbH (LF-006)
            [
                'supplier_number' => 'LF-006',
                'patterns' => [
                    [
                        'pattern_type' => 'email_domain',
                        'pattern_value' => 'kabel-mehr.de',
                        'description' => 'E-Mail Domain von Kabel & Mehr',
                        'confidence_weight' => 9,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'company_name',
                        'pattern_value' => 'Kabel & Mehr Handels GmbH',
                        'description' => 'Vollständiger Firmenname',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'tax_id',
                        'pattern_value' => 'DE345678901',
                        'description' => 'USt-ID von Kabel & Mehr',
                        'confidence_weight' => 10,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Kabelstraße 67',
                        'description' => 'Adresse in PDF-Text',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'DC-Kabel',
                        'description' => 'Spezialisierung auf Kabel',
                        'confidence_weight' => 6,
                        'is_active' => true,
                    ],
                ]
            ],

            // Allgemeine Energieversorger-Pattern
            [
                'supplier_number' => null, // Für alle Supplier
                'patterns' => [
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Marktlokations-ID',
                        'description' => 'Energieversorger-spezifisches Feld',
                        'confidence_weight' => 8,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Netzbetreiber',
                        'description' => 'Energieversorger-spezifisches Feld',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'Zählernummer',
                        'description' => 'Energieversorger-spezifisches Feld',
                        'confidence_weight' => 7,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'pdf_text_contains',
                        'pattern_value' => 'kWh',
                        'description' => 'Energieverbrauch-Einheit',
                        'confidence_weight' => 6,
                        'is_active' => true,
                    ],
                    [
                        'pattern_type' => 'invoice_format',
                        'pattern_value' => 'RE-\d{4}-\d{6}',
                        'description' => 'Standard Rechnungsformat',
                        'confidence_weight' => 5,
                        'is_active' => true,
                    ],
                ]
            ],
        ];

        foreach ($patterns as $supplierPatterns) {
            $supplier = null;
            
            if ($supplierPatterns['supplier_number']) {
                $supplier = $suppliers->get($supplierPatterns['supplier_number']);
                if (!$supplier) {
                    continue; // Supplier nicht gefunden, überspringe
                }
            }

            foreach ($supplierPatterns['patterns'] as $patternData) {
                SupplierRecognitionPattern::create([
                    'supplier_id' => $supplier ? $supplier->id : null,
                    'pattern_type' => $patternData['pattern_type'],
                    'pattern_value' => $patternData['pattern_value'],
                    'description' => $patternData['description'],
                    'confidence_weight' => $patternData['confidence_weight'],
                    'is_active' => $patternData['is_active'],
                ]);
            }
        }
    }
}