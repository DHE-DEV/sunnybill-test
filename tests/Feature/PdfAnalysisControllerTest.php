<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SupplierRecognitionPattern;
use App\Models\PdfExtractionRule;
use App\Models\ContractMatchingRule;
use App\Services\SupplierRecognitionService;
use App\Services\RuleBasedExtractionService;
use App\Services\ContractMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;
    private SupplierContract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Test-Lieferant erstellen
        $this->supplier = Supplier::factory()->create([
            'company_name' => 'Test Energy GmbH',
            'email' => 'info@test-energy.de',
            'tax_id' => 'DE123456789',
            'is_active' => true,
        ]);

        // Test-Vertrag erstellen
        $this->contract = SupplierContract::factory()->create([
            'supplier_id' => $this->supplier->id,
            'contract_number' => 'V-2024-001',
            'reference_number' => 'REF-12345',
            'cost_center' => 'KST-100',
            'is_active' => true,
        ]);

        // Test-Pattern für Lieferanten-Erkennung
        SupplierRecognitionPattern::create([
            'supplier_id' => $this->supplier->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy GmbH',
            'confidence_score' => 0.9,
            'is_active' => true,
        ]);

        // Test-Regel für Datenextraktion
        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'invoice_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        PdfExtractionRule::create([
            'supplier_id' => $this->supplier->id,
            'field_name' => 'contract_number',
            'extraction_method' => 'regex',
            'extraction_pattern' => '/Vertrag[:\s]*([A-Z0-9\-]+)/i',
            'data_type' => 'string',
            'confidence_threshold' => 0.8,
            'is_active' => true,
        ]);

        // Test-Regel für Vertrags-Matching
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer Match',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_analyze_pdf_with_variable_system()
    {
        Storage::fake('local');
        
        // Mock PDF-Inhalt
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-001\nBetrag: 119,00 EUR";
        
        // Mock PDF-Datei erstellen
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        // Mock der PDF-Text-Extraktion
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->post('/analyze-variable', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('pdf-analysis.variable-result');
        $response->assertViewHas('result');
        
        $result = $response->viewData('result');
        $this->assertTrue($result['success']);
        $this->assertEquals($this->supplier->id, $result['supplier_recognition']['supplier_id']);
        $this->assertArrayHasKey('invoice_number', $result['data_extraction']['extracted_data']);
        $this->assertEquals($this->contract->id, $result['contract_matching']['contract_id']);
    }

    /** @test */
    public function it_can_analyze_pdf_with_variable_system_json_response()
    {
        Storage::fake('local');
        
        // Mock PDF-Inhalt
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-001\nBetrag: 119,00 EUR";
        
        // Mock PDF-Datei erstellen
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        // Mock der PDF-Text-Extraktion
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($this->supplier->id, $data['supplier_recognition']['supplier_id']);
        $this->assertArrayHasKey('invoice_number', $data['data_extraction']['extracted_data']);
        $this->assertEquals($this->contract->id, $data['contract_matching']['contract_id']);
    }

    /** @test */
    public function it_handles_unrecognized_supplier()
    {
        Storage::fake('local');
        
        // PDF-Inhalt ohne erkennbare Lieferanten-Pattern
        $pdfContent = "Unknown Company Ltd.\nInvoice: INV-2024-001\nAmount: 100.00 USD";
        
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertFalse($data['success']);
        $this->assertFalse($data['supplier_recognition']['success']);
        $this->assertStringContainsString('Lieferant konnte nicht erkannt werden', $data['error']);
    }

    /** @test */
    public function it_handles_failed_data_extraction()
    {
        Storage::fake('local');
        
        // PDF-Inhalt mit erkennbarem Lieferanten aber ohne extrahierbare Daten
        $pdfContent = "Test Energy GmbH\nSome random text without structured data";
        
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertFalse($data['success']);
        $this->assertTrue($data['supplier_recognition']['success']);
        $this->assertFalse($data['data_extraction']['success']);
        $this->assertStringContainsString('Datenextraktion fehlgeschlagen', $data['error']);
    }

    /** @test */
    public function it_handles_failed_contract_matching()
    {
        Storage::fake('local');
        
        // PDF-Inhalt mit erkennbarem Lieferanten und extrahierbaren Daten, aber ohne passenden Vertrag
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-999"; // Nicht existierender Vertrag
        
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertFalse($data['success']);
        $this->assertTrue($data['supplier_recognition']['success']);
        $this->assertTrue($data['data_extraction']['success']);
        $this->assertFalse($data['contract_matching']['success']);
        $this->assertStringContainsString('Vertragszuordnung fehlgeschlagen', $data['error']);
    }

    /** @test */
    public function it_validates_required_pdf_file()
    {
        $response = $this->postJson('/analyze-variable-json', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pdf_file']);
    }

    /** @test */
    public function it_validates_pdf_file_type()
    {
        $file = UploadedFile::fake()->create('test.txt', 1000, 'text/plain');

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pdf_file']);
    }

    /** @test */
    public function it_validates_pdf_file_size()
    {
        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf'); // 11MB

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pdf_file']);
    }

    /** @test */
    public function it_handles_pdf_parsing_errors()
    {
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('corrupted.pdf', 1000, 'application/pdf');
        
        // Mock PDF-Parser um Exception zu werfen
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) {
            $mock->shouldReceive('parseFile')->andThrow(new \Exception('PDF parsing failed'));
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => 'PDF-Analyse fehlgeschlagen: PDF parsing failed',
        ]);
    }

    /** @test */
    public function it_can_show_variable_analysis_view()
    {
        $response = $this->get('/analyze-variable');

        $response->assertStatus(200);
        $response->assertViewIs('pdf-analysis.variable-form');
    }

    /** @test */
    public function it_includes_confidence_scores_in_response()
    {
        Storage::fake('local');
        
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-001";
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('confidence_scores', $data);
        $this->assertArrayHasKey('supplier_recognition', $data['confidence_scores']);
        $this->assertArrayHasKey('data_extraction', $data['confidence_scores']);
        $this->assertArrayHasKey('contract_matching', $data['confidence_scores']);
        $this->assertArrayHasKey('overall', $data['confidence_scores']);
    }

    /** @test */
    public function it_includes_processing_time_in_response()
    {
        Storage::fake('local');
        
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-001";
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('processing_time', $data);
        $this->assertIsFloat($data['processing_time']);
        $this->assertGreaterThan(0, $data['processing_time']);
    }

    /** @test */
    public function it_includes_matched_patterns_and_rules_in_response()
    {
        Storage::fake('local');
        
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001\nVertrag: V-2024-001";
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('matched_pattern_type', $data['supplier_recognition']);
        $this->assertEquals('company_name', $data['supplier_recognition']['matched_pattern_type']);
        
        $this->assertArrayHasKey('extracted_data', $data['data_extraction']);
        $this->assertArrayHasKey('invoice_number', $data['data_extraction']['extracted_data']);
        $this->assertArrayHasKey('contract_number', $data['data_extraction']['extracted_data']);
        
        $this->assertArrayHasKey('match_type', $data['contract_matching']);
        $this->assertEquals('exact', $data['contract_matching']['match_type']);
    }

    /** @test */
    public function it_handles_multiple_supplier_matches_correctly()
    {
        // Zweiten Lieferanten mit ähnlichem Pattern erstellen
        $supplier2 = Supplier::factory()->create([
            'company_name' => 'Test Energy AG',
            'is_active' => true,
        ]);

        SupplierRecognitionPattern::create([
            'supplier_id' => $supplier2->id,
            'pattern_type' => 'company_name',
            'pattern_value' => 'Test Energy',
            'confidence_score' => 0.7, // Niedrigere Confidence
            'is_active' => true,
        ]);

        Storage::fake('local');
        
        $pdfContent = "Test Energy GmbH\nRechnungsnummer: RE-2024-001";
        $file = UploadedFile::fake()->create('test-invoice.pdf', 1000, 'application/pdf');
        
        $this->mock(\Smalot\PdfParser\Parser::class, function ($mock) use ($pdfContent) {
            $documentMock = \Mockery::mock();
            $documentMock->shouldReceive('getText')->andReturn($pdfContent);
            $mock->shouldReceive('parseFile')->andReturn($documentMock);
        });

        $response = $this->postJson('/analyze-variable-json', [
            'pdf_file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        // Sollte den Lieferanten mit der höheren Confidence wählen
        $this->assertEquals($this->supplier->id, $data['supplier_recognition']['supplier_id']);
        $this->assertEquals(0.9, $data['supplier_recognition']['confidence']);
    }
}