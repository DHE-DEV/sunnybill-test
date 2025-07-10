<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\SupplierRecognitionPattern;
use App\Models\PdfExtractionRule;

class EonSupplierConfigurationSeeder extends Seeder
{
    /**
     * Konfiguriert EON-spezifische Recognition Pattern und Extraction Rules
     */
    public function run(): void
    {
        // Finde EON-Lieferant
        $eonSupplier = Supplier::where('company_name', 'LIKE', '%E.ON%')->first();
        
        if (!$eonSupplier) {
            $this->command->error('EON-Lieferant nicht gefunden!');
            return;
        }

        $this->command->info("Konfiguriere EON-Lieferant: {$eonSupplier->display_name} (ID: {$eonSupplier->id})");

        // 1. EON Recognition Patterns erstellen
        $this->createRecognitionPatterns($eonSupplier);
        
        // 2. EON PDF Extraction Rules erstellen
        $this->createExtractionRules($eonSupplier);
        
        $this->command->info('EON-Konfiguration erfolgreich erstellt!');
    }

    /**
     * Erstellt Recognition Patterns für EON
     */
    private function createRecognitionPatterns(Supplier $supplier): void
    {
        $patterns = [
            [
                'pattern_type' => 'email_domain',
                'pattern_value' => 'eon.de',
                'description' => 'EON E-Mail Domain',
                'confidence_weight' => 9.0,
                'priority' => 1,
            ],
            [
                'pattern_type' => 'sender_email',
                'pattern_value' => 'GKBetreuung@eon.de',
                'description' => 'EON Geschäftskunden-Betreuung E-Mail',
                'confidence_weight' => 10.0,
                'priority' => 1,
            ],
            [
                'pattern_type' => 'company_name',
                'pattern_value' => 'E.ON Energie Deutschland GmbH',
                'description' => 'EON Firmenname',
                'confidence_weight' => 8.0,
                'priority' => 2,
            ],
            [
                'pattern_type' => 'pdf_text_contains',
                'pattern_value' => 'E.ON',
                'description' => 'EON Markenname im PDF',
                'confidence_weight' => 7.0,
                'priority' => 3,
            ],
            [
                'pattern_type' => 'pdf_text_contains',
                'pattern_value' => 'Ihre Monatsrechnung',
                'description' => 'EON typischer Rechnungstext',
                'confidence_weight' => 6.0,
                'priority' => 4,
            ],
            [
                'pattern_type' => 'pdf_text_contains',
                'pattern_value' => 'ZEBI_V02',
                'description' => 'EON Rechnungssystem-Kennung',
                'confidence_weight' => 8.0,
                'priority' => 2,
            ],
        ];

        foreach ($patterns as $patternData) {
            SupplierRecognitionPattern::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'pattern_type' => $patternData['pattern_type'],
                    'pattern_value' => $patternData['pattern_value'],
                ],
                array_merge($patternData, [
                    'supplier_id' => $supplier->id,
                    'is_active' => true,
                    'is_regex' => false,
                    'case_sensitive' => false,
                ])
            );
        }

        $this->command->info('✅ EON Recognition Patterns erstellt');
    }

    /**
     * Erstellt PDF Extraction Rules für EON
     */
    private function createExtractionRules(Supplier $supplier): void
    {
        $rules = [
            // Rechnungsnummer - EON hat mehrzeiliges Format
            [
                'field_name' => 'invoice_number',
                'extraction_method' => 'regex',
                'pattern' => 'Rechnungsnummer\s*\n\s*([0-9\s]{10,20}?)(?=\s*\n)',
                'fallback_pattern' => 'PUW\s+ZRC1\s+([0-9]+)',
                'description' => 'EON Rechnungsnummer mehrzeilig: "Rechnungsnummer" gefolgt von Nummer mit Leerzeichen (optimiert)',
                'priority' => 1,
            ],
            
            // Gesamtbetrag - EON hat "Gesamtbetrag" gefolgt von Betrag
            [
                'field_name' => 'total_amount',
                'extraction_method' => 'regex',
                'pattern' => 'Gesamtbetrag\s*([0-9.,]+)',
                'fallback_pattern' => 'brutto in €.*?([0-9.,]+)',
                'description' => 'EON Gesamtbetrag nach "Gesamtbetrag" oder in Brutto-Spalte',
                'priority' => 1,
            ],
            
            // Rechnungsdatum
            [
                'field_name' => 'invoice_date',
                'extraction_method' => 'regex',
                'pattern' => 'PUW ZRC1 [0-9]+ (\d{1,2}\.\d{1,2}\.\d{4})',
                'fallback_pattern' => '(\d{1,2}\.\d{1,2}\.\d{4})\s+\d{1,2}:\d{2}:\d{2}',
                'description' => 'EON Rechnungsdatum aus PUW-Zeile',
                'priority' => 1,
            ],
            
            // Nettobetrag
            [
                'field_name' => 'net_amount',
                'extraction_method' => 'regex',
                'pattern' => 'Strom\s+([0-9.,]+)',
                'fallback_pattern' => 'netto in €.*?([0-9.,]+)',
                'description' => 'EON Nettobetrag aus Strom-Zeile',
                'priority' => 2,
            ],
            
            // MwSt-Betrag
            [
                'field_name' => 'tax_amount',
                'extraction_method' => 'regex',
                'pattern' => '\(19%\)\s+([0-9.,]+)',
                'fallback_pattern' => 'MwSt\. in €.*?([0-9.,]+)',
                'description' => 'EON MwSt-Betrag (19%)',
                'priority' => 2,
            ],
            
            // Kundennummer
            [
                'field_name' => 'customer_number',
                'extraction_method' => 'regex',
                'pattern' => 'Kunde:\s*([0-9]+)(?=\s)',
                'description' => 'EON Kundennummer nach "Kunde:" (nur Zahlen, gefolgt von Leerzeichen)',
                'priority' => 3,
            ],
            
            // Vertragskonto - EON hat mehrzeiliges Format
            [
                'field_name' => 'contract_account',
                'extraction_method' => 'regex',
                'pattern' => 'Vertragskonto\s*\n\s*([0-9\s]+)',
                'description' => 'EON Vertragskonto mehrzeilig: "Vertragskonto" gefolgt von Nummer mit Leerzeichen',
                'priority' => 3,
            ],
            
            // Verbrauchsstelle
            [
                'field_name' => 'consumption_site',
                'extraction_method' => 'regex',
                'pattern' => 'Verbrauchsstelle:([^\\n]+)',
                'description' => 'EON Verbrauchsstelle',
                'priority' => 3,
            ],
            
            // Abrechnungszeitraum Start
            [
                'field_name' => 'period_start',
                'extraction_method' => 'regex',
                'pattern' => '(\d{2}\.\d{2}\.\d{2})\d{2}\.\d{2}\.\d{2}',
                'description' => 'EON Abrechnungszeitraum Start',
                'priority' => 4,
            ],
            
            // Abrechnungszeitraum Ende
            [
                'field_name' => 'period_end',
                'extraction_method' => 'regex',
                'pattern' => '\d{2}\.\d{2}\.\d{2}(\d{2}\.\d{2}\.\d{2})',
                'description' => 'EON Abrechnungszeitraum Ende',
                'priority' => 4,
            ],
        ];

        foreach ($rules as $ruleData) {
            PdfExtractionRule::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'field_name' => $ruleData['field_name'],
                ],
                array_merge($ruleData, [
                    'supplier_id' => $supplier->id,
                    'is_active' => true,
                    'options' => [
                        'case_sensitive' => false,
                        'multiline' => true,
                    ],
                ])
            );
        }

        $this->command->info('✅ EON PDF Extraction Rules erstellt');
    }
}