<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\ContractMatchingRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ContractMatchingService
{
    /**
     * Findet passende Verträge für einen Lieferanten basierend auf extrahierten Daten
     */
    public function findMatchingContracts(
        Supplier $supplier, 
        array $extractedData, 
        string $pdfText, 
        string $emailText = '',
        array $metadata = []
    ): array {
        $contracts = SupplierContract::where('supplier_id', $supplier->id)
            ->active()
            ->with('activeContractMatchingRules')
            ->get();

        if ($contracts->isEmpty()) {
            Log::info('Keine aktiven Verträge für Lieferanten gefunden', [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->display_name
            ]);
            return [];
        }

        $matches = [];
        $sourceData = $this->prepareSourceData($extractedData, $pdfText, $emailText, $metadata);

        foreach ($contracts as $contract) {
            $confidence = $this->calculateContractConfidence($contract, $sourceData);
            
            if ($confidence > 0.1) { // Mindest-Confidence von 10%
                $matchingFields = $this->getMatchingFields($contract, $sourceData);
                
                $matches[] = [
                    'contract' => $contract,
                    'confidence' => $confidence,
                    'matching_fields' => $matchingFields,
                    'match_details' => $this->getMatchDetails($contract, $sourceData),
                ];
            }
        }

        // Sortiere nach Confidence absteigend
        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        Log::info('Vertragsmatching abgeschlossen', [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->display_name,
            'total_contracts' => $contracts->count(),
            'matching_contracts' => count($matches),
            'best_match_confidence' => $matches[0]['confidence'] ?? 0,
        ]);

        return $matches;
    }

    /**
     * Bereitet die Quelldaten für das Matching vor
     */
    private function prepareSourceData(array $extractedData, string $pdfText, string $emailText, array $metadata): array
    {
        return [
            'extracted_data' => $extractedData,
            'pdf_text' => $pdfText,
            'email_text' => $emailText,
            'email_subject' => $metadata['email_subject'] ?? '',
            'sender_email' => $metadata['sender_email'] ?? '',
            'sender_name' => $metadata['sender_name'] ?? '',
        ];
    }

    /**
     * Berechnet die Confidence für einen Vertrag
     */
    private function calculateContractConfidence(SupplierContract $contract, array $sourceData): float
    {
        $rules = $contract->activeContractMatchingRules;
        
        if ($rules->isEmpty()) {
            // Fallback: Versuche automatisches Matching basierend auf Vertragsdaten
            return $this->calculateFallbackConfidence($contract, $sourceData);
        }

        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($rules as $rule) {
            $value = $rule->extractValueFromSource($sourceData);
            
            if ($value !== null) {
                $ruleConfidence = $rule->calculateConfidence($value);
                $weightedScore += $ruleConfidence * $rule->confidence_weight;
                $totalWeight += $rule->confidence_weight;
                
                Log::debug('Contract Matching Rule angewendet', [
                    'contract_id' => $contract->id,
                    'rule_id' => $rule->id,
                    'field_source' => $rule->field_source,
                    'field_name' => $rule->field_name,
                    'extracted_value' => $value,
                    'rule_confidence' => $ruleConfidence,
                    'weight' => $rule->confidence_weight,
                ]);
            }
        }

        return $totalWeight > 0 ? $weightedScore / $totalWeight : 0;
    }

    /**
     * Fallback-Confidence-Berechnung ohne explizite Regeln
     */
    private function calculateFallbackConfidence(SupplierContract $contract, array $sourceData): float
    {
        $confidence = 0;
        $checks = 0;

        // Prüfe Vertragsnummer
        if ($contract->contract_number) {
            $checks++;
            if ($this->valueExistsInSources($contract->contract_number, $sourceData)) {
                $confidence += 0.8;
            }
        }

        // Prüfe externe Vertragsnummer
        if ($contract->external_contract_number) {
            $checks++;
            if ($this->valueExistsInSources($contract->external_contract_number, $sourceData)) {
                $confidence += 0.7;
            }
        }

        // Prüfe Gläubigernummer
        if ($contract->creditor_number) {
            $checks++;
            if ($this->valueExistsInSources($contract->creditor_number, $sourceData)) {
                $confidence += 0.6;
            }
        }

        // Prüfe Contract Recognition Felder
        foreach (['contract_recognition_1', 'contract_recognition_2', 'contract_recognition_3'] as $field) {
            if ($contract->$field) {
                $checks++;
                if ($this->valueExistsInSources($contract->$field, $sourceData)) {
                    $confidence += 0.5;
                }
            }
        }

        return $checks > 0 ? min(1.0, $confidence / $checks) : 0;
    }

    /**
     * Prüft ob ein Wert in den Quelldaten existiert
     */
    private function valueExistsInSources(string $value, array $sourceData): bool
    {
        $searchSources = [
            $sourceData['pdf_text'] ?? '',
            $sourceData['email_text'] ?? '',
            $sourceData['email_subject'] ?? '',
        ];

        // Prüfe auch extrahierte Daten
        if (isset($sourceData['extracted_data'])) {
            $searchSources = array_merge($searchSources, array_values($sourceData['extracted_data']));
        }

        foreach ($searchSources as $source) {
            if (str_contains(strtolower($source), strtolower($value))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ermittelt die übereinstimmenden Felder für einen Vertrag
     */
    private function getMatchingFields(SupplierContract $contract, array $sourceData): array
    {
        $matchingFields = [];
        $rules = $contract->activeContractMatchingRules;

        foreach ($rules as $rule) {
            $value = $rule->extractValueFromSource($sourceData);
            
            if ($value !== null && $rule->matches($value)) {
                $matchingFields[] = [
                    'field_source' => $rule->field_source,
                    'field_name' => $rule->field_name,
                    'pattern' => $rule->matching_pattern,
                    'match_type' => $rule->match_type,
                    'extracted_value' => $value,
                    'confidence' => $rule->calculateConfidence($value),
                ];
            }
        }

        return $matchingFields;
    }

    /**
     * Ermittelt detaillierte Match-Informationen
     */
    private function getMatchDetails(SupplierContract $contract, array $sourceData): array
    {
        $details = [
            'contract_number_match' => false,
            'external_contract_number_match' => false,
            'creditor_number_match' => false,
            'recognition_fields_match' => [],
            'rule_matches' => [],
        ];

        // Prüfe Vertragsnummer
        if ($contract->contract_number && $this->valueExistsInSources($contract->contract_number, $sourceData)) {
            $details['contract_number_match'] = true;
        }

        // Prüfe externe Vertragsnummer
        if ($contract->external_contract_number && $this->valueExistsInSources($contract->external_contract_number, $sourceData)) {
            $details['external_contract_number_match'] = true;
        }

        // Prüfe Gläubigernummer
        if ($contract->creditor_number && $this->valueExistsInSources($contract->creditor_number, $sourceData)) {
            $details['creditor_number_match'] = true;
        }

        // Prüfe Recognition Felder
        foreach (['contract_recognition_1', 'contract_recognition_2', 'contract_recognition_3'] as $field) {
            if ($contract->$field && $this->valueExistsInSources($contract->$field, $sourceData)) {
                $details['recognition_fields_match'][] = $field;
            }
        }

        // Prüfe Regel-Matches
        foreach ($contract->activeContractMatchingRules as $rule) {
            $value = $rule->extractValueFromSource($sourceData);
            if ($value !== null) {
                $details['rule_matches'][] = [
                    'rule_id' => $rule->id,
                    'field_source' => $rule->field_source,
                    'field_name' => $rule->field_name,
                    'matches' => $rule->matches($value),
                    'confidence' => $rule->calculateConfidence($value),
                    'extracted_value' => $value,
                ];
            }
        }

        return $details;
    }

    /**
     * Findet den besten Vertrag für gegebene Daten
     */
    public function findBestContract(
        Supplier $supplier, 
        array $extractedData, 
        string $pdfText, 
        string $emailText = '',
        array $metadata = []
    ): ?array {
        $matches = $this->findMatchingContracts($supplier, $extractedData, $pdfText, $emailText, $metadata);
        
        return empty($matches) ? null : $matches[0];
    }

    /**
     * Testet eine Contract Matching Rule gegen gegebene Daten
     */
    public function testRule(ContractMatchingRule $rule, array $sourceData): array
    {
        $value = $rule->extractValueFromSource($sourceData);
        $matches = $value !== null ? $rule->matches($value) : false;
        $confidence = $matches ? $rule->calculateConfidence($value) : 0;

        return [
            'rule_id' => $rule->id,
            'field_source' => $rule->field_source,
            'field_name' => $rule->field_name,
            'matching_pattern' => $rule->matching_pattern,
            'match_type' => $rule->match_type,
            'extracted_value' => $value,
            'matches' => $matches,
            'confidence' => $confidence,
            'case_sensitive' => $rule->case_sensitive,
            'confidence_weight' => $rule->confidence_weight,
        ];
    }

    /**
     * Testet alle Regeln eines Vertrags
     */
    public function testAllRulesForContract(SupplierContract $contract, array $sourceData): array
    {
        $rules = $contract->activeContractMatchingRules;
        $results = [];

        foreach ($rules as $rule) {
            $results[] = $this->testRule($rule, $sourceData);
        }

        $totalConfidence = $this->calculateContractConfidence($contract, $sourceData);

        return [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'total_rules' => $rules->count(),
            'successful_matches' => collect($results)->where('matches', true)->count(),
            'total_confidence' => $totalConfidence,
            'rule_results' => $results,
        ];
    }

    /**
     * Generiert automatische Matching-Regeln für einen Vertrag
     */
    public function generateRulesForContract(SupplierContract $contract): Collection
    {
        $rules = collect();

        // Vertragsnummer-Regel
        if ($contract->contract_number) {
            $rules->push([
                'field_source' => 'extracted_data',
                'field_name' => 'contract_number',
                'matching_pattern' => $contract->contract_number,
                'match_type' => 'exact',
                'confidence_weight' => 10,
                'description' => 'Automatisch generiert: Exakte Übereinstimmung Vertragsnummer',
            ]);

            $rules->push([
                'field_source' => 'pdf_text',
                'field_name' => null,
                'matching_pattern' => $contract->contract_number,
                'match_type' => 'contains',
                'confidence_weight' => 8,
                'description' => 'Automatisch generiert: Vertragsnummer im PDF-Text',
            ]);
        }

        // Externe Vertragsnummer-Regel
        if ($contract->external_contract_number) {
            $rules->push([
                'field_source' => 'extracted_data',
                'field_name' => 'contract_number',
                'matching_pattern' => $contract->external_contract_number,
                'match_type' => 'exact',
                'confidence_weight' => 9,
                'description' => 'Automatisch generiert: Externe Vertragsnummer',
            ]);
        }

        // Gläubigernummer-Regel
        if ($contract->creditor_number) {
            $rules->push([
                'field_source' => 'extracted_data',
                'field_name' => 'creditor_number',
                'matching_pattern' => $contract->creditor_number,
                'match_type' => 'exact',
                'confidence_weight' => 8,
                'description' => 'Automatisch generiert: Gläubigernummer',
            ]);
        }

        // Contract Recognition Felder
        foreach (['contract_recognition_1', 'contract_recognition_2', 'contract_recognition_3'] as $index => $field) {
            if ($contract->$field) {
                $rules->push([
                    'field_source' => 'pdf_text',
                    'field_name' => null,
                    'matching_pattern' => $contract->$field,
                    'match_type' => 'contains',
                    'confidence_weight' => 7 - $index,
                    'description' => "Automatisch generiert: Recognition Field " . ($index + 1),
                ]);
            }
        }

        return $rules;
    }

    /**
     * Analysiert die Matching-Performance für einen Lieferanten
     */
    public function analyzeMatchingPerformance(Supplier $supplier): array
    {
        $contracts = SupplierContract::where('supplier_id', $supplier->id)
            ->active()
            ->with('activeContractMatchingRules')
            ->get();

        $analysis = [
            'total_contracts' => $contracts->count(),
            'contracts_with_rules' => 0,
            'contracts_without_rules' => 0,
            'total_rules' => 0,
            'rules_by_type' => [],
            'rules_by_source' => [],
            'average_rules_per_contract' => 0,
        ];

        foreach ($contracts as $contract) {
            $ruleCount = $contract->activeContractMatchingRules->count();
            $analysis['total_rules'] += $ruleCount;

            if ($ruleCount > 0) {
                $analysis['contracts_with_rules']++;
            } else {
                $analysis['contracts_without_rules']++;
            }

            foreach ($contract->activeContractMatchingRules as $rule) {
                $analysis['rules_by_type'][$rule->match_type] = ($analysis['rules_by_type'][$rule->match_type] ?? 0) + 1;
                $analysis['rules_by_source'][$rule->field_source] = ($analysis['rules_by_source'][$rule->field_source] ?? 0) + 1;
            }
        }

        $analysis['average_rules_per_contract'] = $analysis['total_contracts'] > 0 
            ? round($analysis['total_rules'] / $analysis['total_contracts'], 2) 
            : 0;

        return $analysis;
    }
}