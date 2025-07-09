<?php

namespace Tests\Unit\Services;

use App\Models\Supplier;
use App\Models\SupplierRecognitionPattern;
use App\Services\SupplierRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierRecognitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupplierRecognitionService $service;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SupplierRecognitionService::class);
        
        // Test-Lieferant erstellen
        $this->supplier = Supplier::factory()->create([
            'company_name' => 'Test Energy GmbH',
            'email' => 'info@test-energy.de',
            'tax_id' => 'DE123456789',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_recognize_supplier_by_email_domain()
    {
        // Pattern für E-Mail-Domain erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'email_domain',
            'pattern_value' => 'test-energy.de',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung von info@test-energy.de\nRechnungsnummer: RE-2024-001";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_id']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
        $this->assertEquals('email_domain', $result['matched_pattern_type']);
    }

    /** @test */
    public function it_can_recognize_supplier_by_company_name()
    {
        // Pattern für Firmenname erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.95,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung\nTest Energy GmbH\nMusterstraße 123";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_id']);
        $this->assertGreaterThanOrEqual(0.95, $result['confidence']);
        $this->assertEquals('company_name', $result['matched_pattern_type']);
    }

    /** @test */
    public function it_can_recognize_supplier_by_tax_id()
    {
        // Pattern für Steuer-ID erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'tax_id',
            'pattern_value' => 'DE123456789',
            'confidence_score' => 0.98,
            'is_active' => true,
        ]);

        $pdfText = "Steuernummer: DE123456789\nRechnungsdatum: 15.07.2024";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_id']);
        $this->assertGreaterThanOrEqual(0.98, $result['confidence']);
        $this->assertEquals('tax_id', $result['matched_pattern_type']);
    }

    /** @test */
    public function it_can_recognize_supplier_by_regex_pattern()
    {
        // Pattern für Regex erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'regex',
            'pattern_value' => '/Test\s+Energy\s+GmbH/i',
            'confidence_score' => 0.85,
            'is_active' => true,
        ]);

        $pdfText = "Lieferant: TEST ENERGY GMBH\nAdresse: Musterweg 456";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_id']);
        $this->assertGreaterThanOrEqual(0.85, $result['confidence']);
        $this->assertEquals('regex', $result['matched_pattern_type']);
    }

    /** @test */
    public function it_returns_highest_confidence_match_when_multiple_patterns_match()
    {
        // Mehrere Pattern mit unterschiedlichen Confidence-Werten erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy',
            'confidence_score' => 0.7,
            'is_active' => true,
        ]);

        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'email_domain',
            'pattern_value' => 'test-energy.de',
            'confidence_score' => 0.95,
            'is_active' => true,
        ]);

        $pdfText = "Test Energy GmbH\nKontakt: info@test-energy.de";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_id']);
        $this->assertEquals(0.95, $result['confidence']);
        $this->assertEquals('email_domain', $result['matched_pattern_type']);
    }

    /** @test */
    public function it_ignores_inactive_patterns()
    {
        // Inaktives Pattern erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.9,
            'is_active' => false,
        ]);

        $pdfText = "Rechnung von Test Energy GmbH";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['supplier_id']);
        $this->assertEquals(0, $result['confidence']);
    }

    /** @test */
    public function it_returns_failure_when_no_pattern_matches()
    {
        // Pattern für anderen Text erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Other Company',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        $pdfText = "Rechnung von Unknown Supplier Ltd.";
        
        $result = $this->service->recognizeSupplier($pdfText);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['supplier_id']);
        $this->assertEquals(0, $result['confidence']);
    }

    /** @test */
    public function it_handles_empty_pdf_text()
    {
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        $result = $this->service->recognizeSupplier('');
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['supplier_id']);
        $this->assertEquals(0, $result['confidence']);
    }

    /** @test */
    public function it_can_get_all_patterns_for_supplier()
    {
        // Mehrere Pattern für den Lieferanten erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'email_domain',
            'pattern_value' => 'test-energy.de',
            'confidence_score' => 0.85,
            'is_active' => true,
        ]);

        $patterns = $this->service->getPatternsForSupplier($this->supplier->id);
        
        $this->assertCount(2, $patterns);
        $this->assertEquals('Test Energy GmbH', $patterns->first()->pattern_value);
    }

    /** @test */
    public function it_can_test_pattern_against_text()
    {
        $pattern = SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        $testText = "Rechnung von Test Energy GmbH";
        
        $result = $this->service->testPattern($pattern, $testText);
        
        $this->assertTrue($result['match']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    /** @test */
    public function it_handles_invalid_regex_patterns_gracefully()
    {
        $pattern = SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'regex',
            'pattern_value' => '/[invalid regex/',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        $testText = "Some test text";
        
        $result = $this->service->testPattern($pattern, $testText);
        
        $this->assertFalse($result['match']);
        $this->assertEquals(0, $result['confidence']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_respects_confidence_threshold()
    {
        // Pattern mit niedriger Confidence erstellen
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.3, // Niedrige Confidence
            'is_active' => true,
        ]);

        $pdfText = "Test Energy GmbH";
        
        // Mit Standard-Threshold (0.5)
        $result = $this->service->recognizeSupplier($pdfText);
        $this->assertFalse($result['success']);
        
        // Mit niedrigerem Threshold
        $result = $this->service->recognizeSupplier($pdfText, 0.2);
        $this->assertTrue($result['success']);
    }
}