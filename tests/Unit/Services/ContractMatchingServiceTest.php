<?php

namespace Tests\Unit\Services;

use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\ContractMatchingRule;
use App\Services\ContractMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContractMatchingService $service;
    private Supplier $supplier;
    private SupplierContract $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ContractMatchingService::class);
        
        // Test-Lieferant erstellen
        $this->supplier = Supplier::factory()->create([
            'company_name' => 'Test Energy GmbH',
            'is_active' => true,
        ]);

        // Test-Vertrag erstellen
        $this->contract = SupplierContract::factory()->create([
            'supplier_id' => $this->supplier->id,
            'contract_number' => 'V-2024-001',
            'reference_number' => 'REF-12345',
            'cost_center' => 'KST-100',
            'project_code' => 'PROJ-A',
            'description' => 'Stromlieferung Hauptgebäude',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_match_contract_by_exact_match()
    {
        // Exakte Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer Exact Match',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertEquals(1.0, $result['confidence']);
        $this->assertEquals('exact', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_partial_match()
    {
        // Partial Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Referenz Partial Match',
            'source_field' => 'reference_number',
            'target_field' => 'reference_number',
            'match_type' => 'partial',
            'match_threshold' => 0.7,
            'is_active' => true,
        ]);

        $extractedData = [
            'reference_number' => 'REF-12345-EXTRA',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
        $this->assertEquals('partial', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_fuzzy_match()
    {
        // Fuzzy Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Beschreibung Fuzzy Match',
            'source_field' => 'description',
            'target_field' => 'description',
            'match_type' => 'fuzzy',
            'match_threshold' => 0.6,
            'is_active' => true,
        ]);

        $extractedData = [
            'description' => 'Strom Lieferung Haupt Gebäude',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.6, $result['confidence']);
        $this->assertEquals('fuzzy', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_regex_pattern()
    {
        // Regex Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer Regex Match',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'regex',
            'match_pattern' => '/^V-\d{4}-\d{3}$/',
            'match_threshold' => 0.9,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.9, $result['confidence']);
        $this->assertEquals('regex', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_contains_match()
    {
        // Contains Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Kostenstelle Contains Match',
            'source_field' => 'cost_center',
            'target_field' => 'cost_center',
            'match_type' => 'contains',
            'match_pattern' => 'KST-100, KST-200',
            'match_threshold' => 0.8,
            'is_active' => true,
        ]);

        $extractedData = [
            'cost_center' => 'Kostenstelle: KST-100 (Hauptgebäude)',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
        $this->assertEquals('contains', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_starts_with_match()
    {
        // Starts With Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Projektcode Starts With Match',
            'source_field' => 'project_code',
            'target_field' => 'project_code',
            'match_type' => 'starts_with',
            'match_pattern' => 'PROJ-A, PROJ-B',
            'match_threshold' => 0.8,
            'is_active' => true,
        ]);

        $extractedData = [
            'project_code' => 'PROJ-A-SUBPROJECT',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
        $this->assertEquals('starts_with', $result['match_type']);
    }

    /** @test */
    public function it_can_match_contract_by_ends_with_match()
    {
        // Ends With Match-Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer Ends With Match',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'ends_with',
            'match_pattern' => '-001, -002',
            'match_threshold' => 0.8,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'PREFIX-V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.8, $result['confidence']);
        $this->assertEquals('ends_with', $result['match_type']);
    }

    /** @test */
    public function it_applies_preprocessing_rules()
    {
        // Regel mit Preprocessing erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer mit Preprocessing',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'case_sensitive' => false,
            'normalize_whitespace' => true,
            'remove_special_chars' => true,
            'preprocessing_rules' => "trim\nuppercase",
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => '  v-2024-001  ',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
    }

    /** @test */
    public function it_uses_fallback_rules_when_main_rule_fails()
    {
        // Regel mit Fallback erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Vertragsnummer mit Fallback',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'fallback_rules' => "partial:0.8\nfuzzy:0.6",
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001-MODIFIED',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']);
        $this->assertGreaterThanOrEqual(0.6, $result['confidence']);
    }

    /** @test */
    public function it_respects_priority_order_when_multiple_rules_exist()
    {
        // Regel mit niedriger Priorität
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Niedrige Priorität',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'partial',
            'match_threshold' => 0.5,
            'priority' => 10,
            'is_active' => true,
        ]);

        // Regel mit hoher Priorität
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Hohe Priorität',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'priority' => 1,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1.0, $result['confidence']);
        $this->assertEquals('exact', $result['match_type']);
    }

    /** @test */
    public function it_ignores_inactive_rules()
    {
        // Inaktive Regel erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Inaktive Regel',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => false,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['contract_id']);
        $this->assertEquals(0, $result['confidence']);
    }

    /** @test */
    public function it_returns_highest_confidence_match_when_multiple_contracts_match()
    {
        // Zweiten Vertrag erstellen
        $contract2 = SupplierContract::factory()->create([
            'supplier_id' => $this->supplier->id,
            'contract_number' => 'V-2024-002',
            'reference_number' => 'REF-54321',
            'is_active' => true,
        ]);

        // Regel erstellen, die beide Verträge matchen könnte
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Partial Match Regel',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'partial',
            'match_threshold' => 0.5,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001-EXACT',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->contract->id, $result['contract_id']); // Sollte den ersten Vertrag matchen (höhere Ähnlichkeit)
    }

    /** @test */
    public function it_handles_missing_source_fields()
    {
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Test Regel',
            'source_field' => 'missing_field',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024-001',
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['contract_id']);
    }

    /** @test */
    public function it_can_test_matching_rule()
    {
        $rule = ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Test Regel',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => true,
        ]);

        $testValue = 'V-2024-001';
        $contractValue = 'V-2024-001';
        
        $result = $this->service->testRule($rule, $testValue, $contractValue);
        
        $this->assertTrue($result['match']);
        $this->assertEquals(1.0, $result['score']);
    }

    /** @test */
    public function it_can_get_rules_for_supplier()
    {
        // Mehrere Regeln für den Lieferanten erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Regel 1',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'priority' => 1,
            'is_active' => true,
        ]);

        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Regel 2',
            'source_field' => 'reference_number',
            'target_field' => 'reference_number',
            'match_type' => 'partial',
            'priority' => 2,
            'is_active' => true,
        ]);

        $rules = $this->service->getRulesForSupplier($this->supplier->id);
        
        $this->assertCount(2, $rules);
        $this->assertEquals('Regel 1', $rules->first()->rule_name);
        $this->assertEquals(1, $rules->first()->priority);
    }

    /** @test */
    public function it_handles_invalid_regex_patterns_gracefully()
    {
        $rule = ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Invalid Regex',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'regex',
            'match_pattern' => '/[invalid regex/',
            'match_threshold' => 0.9,
            'is_active' => true,
        ]);

        $testValue = 'V-2024-001';
        $contractValue = 'V-2024-001';
        
        $result = $this->service->testRule($rule, $testValue, $contractValue);
        
        $this->assertFalse($result['match']);
        $this->assertEquals(0, $result['score']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_respects_confidence_threshold()
    {
        // Regel mit hohem Threshold erstellen
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Hoher Threshold',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'partial',
            'match_threshold' => 0.9,
            'is_active' => true,
        ]);

        $extractedData = [
            'contract_number' => 'V-2024', // Nur teilweise Match
            'invoice_number' => 'RE-2024-001',
        ];

        $result = $this->service->findMatchingContract($this->supplier->id, $extractedData);
        
        $this->assertFalse($result['success']); // Sollte fehlschlagen wegen niedrigem Confidence
    }

    /** @test */
    public function it_handles_empty_extracted_data()
    {
        ContractMatchingRule::create([
            'supplier_id' => $this->supplier->id,
            'rule_name' => 'Test Regel',
            'source_field' => 'contract_number',
            'target_field' => 'contract_number',
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'is_active' => true,
        ]);

        $result = $this->service->findMatchingContract($this->supplier->id, []);
        
        $this->assertFalse($result['success']);
        $this->assertNull($result['contract_id']);
    }
}