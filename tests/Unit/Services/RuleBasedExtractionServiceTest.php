<?php

namespace Tests\Unit\Services;

use App\Models\Supplier;
use App\Models\PdfExtractionRule;
use App\Services\RuleBasedExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleBasedExtractionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RuleBasedExtractionService $service;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RuleBasedExtractionService::class);
        
        // Test-Lieferant erstellen
        $this->supplier = Supplier::factory()->create([
            'company_name' => 'Test Energy GmbH',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_extract_data_using_regex_pattern()
    {
        // Regex-Regel für Rechnungsnummer erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung\nRechnungsnummer: RE-2024-001\nDatum: 15.07.2024";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('invoice_number', $result['extracted_data']);
        $this->assertEquals('RE-2024-001', $result['extracted_data']['invoice_number']['value']);
        $this->assertGreaterThanOrEqual(0.8, $result['extracted_data']['invoice_number']['confidence']);
    }

    /** @test */
    public function it_can_extract_data_using_keyword_search()
    {
        // Keyword-Regel für Gesamtbetrag erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'total_amount',
            'extraction_method' => 'keyword_search',
            'extraction_pattern' => 'Gesamtbetrag:, Total:, Summe:',
            'data_type' => 'decimal',
            'confidence_threshold' => 0.7,
            'is_active' => true,
        ]);

        $pdfText = "Positionen:\nPosition 1: 100,00 EUR\nGesamtbetrag: 119,00 EUR";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('total_amount', $result['extracted_data']);
        $this->assertEquals('119,00', $result['extracted_data']['total_amount']['value']);
    }

    /** @test */
    public function it_can_extract_data_using_position_based_method()
    {
        // Position-basierte Regel erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'due_date',
            'extraction_method' => 'position_based',
            'extraction_pattern' => 'line:3, column:15-25',
            'data_type' => 'date',
            'confidence_threshold' => 0.6,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung RE-001\nDatum: 15.07.2024\nFällig am: 30.07.2024\nBetrag: 119,00 EUR";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('due_date', $result['extracted_data']);
        $this->assertEquals('30.07.2024', $result['extracted_data']['due_date']['value']);
    }

    /** @test */
    public function it_can_extract_data_using_line_pattern()
    {
        // Line-Pattern-Regel erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'contract_number',
            'extraction_method' => 'line_pattern',
            'extraction_pattern' => 'Vertrag*',
            'data_type' => 'string',
            'confidence_threshold' => 0.7,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung für Vertrag V-2024-123\nKunde: Max Mustermann\nBetrag: 500,00 EUR";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('contract_number', $result['extracted_data']);
        $this->assertStringContainsString('V-2024-123', $result['extracted_data']['contract_number']['value']);
    }

    /** @test */
    public function it_applies_data_type_transformations()
    {
        // Regel für Dezimalzahl erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'amount',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Betrag[:\s]*([0-9,\.]+)\s*EUR/i',
            'data_type' => 'decimal',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $pdfText = "Rechnungsbetrag: 1.234,56 EUR";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('amount', $result['extracted_data']);
        $this->assertEquals('1.234,56', $result['extracted_data']['amount']['value']);
        $this->assertEquals('decimal', $result['extracted_data']['amount']['data_type']);
    }

    /** @test */
    public function it_uses_fallback_patterns_when_main_pattern_fails()
    {
        // Regel mit Fallback-Pattern erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Invoice[:\s]*([A-Z0-9\-]+)/i', // Englisches Pattern
            'fallback_patterns' => "/Rechnung[:\s]*([A-Z0-9\-]+)/i\n/RG[:\s]*([A-Z0-9\-]+)/i",
            'data_type' => 'string',
            'confidence_threshold' => 0.7,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung RG-2024-001\nDatum: 15.07.2024";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('invoice_number', $result['extracted_data']);
        $this->assertEquals('RG-2024-001', $result['extracted_data']['invoice_number']['value']);
    }

    /** @test */
    public function it_applies_transformation_rules()
    {
        // Regel mit Transformationen erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'customer_name',
            'extraction_method' => 'keyword_search',
            'extraction_pattern' => 'Kunde:, Customer:',
            'data_type' => 'string',
            'transformation_rules' => "trim\nuppercase\nremove_spaces",
            'confidence_threshold' => 0.6,
            'is_active' => true,
        ]);

        $pdfText = "Kunde:   max mustermann   \nAdresse: Musterstraße 123";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('customer_name', $result['extracted_data']);
        $this->assertEquals('MAXMUSTERMANN', $result['extracted_data']['customer_name']['value']);
    }

    /** @test */
    public function it_validates_extracted_values_with_validation_regex()
    {
        // Regel mit Validierungs-Regex erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Nummer[:\s]*([A-Z0-9\-]+)/i',
            'validation_regex' => '/^[A-Z]{2}-\d{4}-\d{3}$/',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        // Gültiger Wert
        $pdfText1 = "Rechnungsnummer: RE-2024-001";
        $result1 = $this->service->extractData($this->supplier->id, $pdfText1);
        
        $this->assertTrue($result1['success']);
        $this->assertEquals('RE-2024-001', $result1['extracted_data']['invoice_number']['value']);

        // Ungültiger Wert
        $pdfText2 = "Rechnungsnummer: INVALID123";
        $result2 = $this->service->extractData($this->supplier->id, $pdfText2);
        
        $this->assertFalse($result2['success']);
        $this->assertArrayNotHasKey('invoice_number', $result2['extracted_data']);
    }

    /** @test */
    public function it_respects_priority_order_when_multiple_rules_exist()
    {
        // Regel mit niedriger Priorität
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Nummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'priority' => 10,
            'confidence_threshold' => 0.7,
            'is_active' => true,
        ]);

        // Regel mit hoher Priorität
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'priority' => 1,
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $pdfText = "Rechnungsnummer: RE-2024-001\nNummer: ALT-123";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('RE-2024-001', $result['extracted_data']['invoice_number']['value']);
    }

    /** @test */
    public function it_ignores_inactive_rules()
    {
        // Inaktive Regel erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => false,
        ]);

        $pdfText = "Rechnungsnummer: RE-2024-001";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['extracted_data']);
    }

    /** @test */
    public function it_handles_required_fields_correctly()
    {
        // Pflichtfeld erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Invoice[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'is_required' => true,
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        // Optionales Feld erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'notes',
            'extraction_method' => 'keyword_search',
            'extraction_pattern' => 'Notiz:, Note:',
            'data_type' => 'string',
            'is_required' => false,
            'confidence_threshold' => 0.6,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung ohne Invoice Number\nNotiz: Test-Notiz";
        
        $result = $this->service->extractData($this->supplier->id, $pdfText);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('missing_required_fields', $result);
        $this->assertContains('invoice_number', $result['missing_required_fields']);
    }

    /** @test */
    public function it_can_generate_rules_automatically()
    {
        $sampleTexts = [
            "Rechnungsnummer: RE-2024-001\nBetrag: 100,00 EUR",
            "Rechnungsnummer: RE-2024-002\nBetrag: 200,50 EUR",
            "Rechnungsnummer: RE-2024-003\nBetrag: 300,75 EUR",
        ];

        $generatedRules = $this->service->generateRulesFromSamples($this->supplier->id, $sampleTexts);
        
        $this->assertNotEmpty($generatedRules);
        $this->assertArrayHasKey('invoice_number', $generatedRules);
        $this->assertArrayHasKey('amount', $generatedRules);
    }

    /** @test */
    public function it_can_test_extraction_rule()
    {
        $rule = PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $testText = "Rechnungsnummer: RE-2024-001";
        
        $result = $this->service->testRule($rule, $testText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('RE-2024-001', $result['extracted_value']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
    }

    /** @test */
    public function it_handles_empty_pdf_text()
    {
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $result = $this->service->extractData($this->supplier->id, '');
        
        $this->assertFalse($result['success']);
        $this->assertEmpty($result['extracted_data']);
    }

    /** @test */
    public function it_handles_invalid_regex_patterns_gracefully()
    {
        $rule = PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/[invalid regex/',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        $testText = "Rechnungsnummer: RE-2024-001";
        
        $result = $this->service->testRule($rule, $testText);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_get_rules_for_supplier()
    {
        // Mehrere Regeln für den Lieferanten erstellen
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'priority' => 1,
            'is_active' => true,
        ]);

        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'total_amount',
            'extraction_method' => 'keyword_search',
            'extraction_pattern' => 'Gesamtbetrag:',
            'data_type' => 'decimal',
            'priority' => 2,
            'is_active' => true,
        ]);

        $rules = $this->service->getRulesForSupplier($this->supplier->id);
        
        $this->assertCount(2, $rules);
        $this->assertEquals('invoice_number', $rules->first()->field_name);
        $this->assertEquals(1, $rules->first()->priority);
    }
}