<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\ContractMatchingRule;

class EonContractMatchingRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Finde EON Supplier
        $eonSupplier = Supplier::where('name', 'LIKE', '%EON%')
            ->orWhere('company_name', 'LIKE', '%EON%')
            ->first();

        if (!$eonSupplier) {
            // Erstelle EON Supplier falls nicht vorhanden mit eindeutiger supplier_number
            $uniqueNumber = 'EON-' . time();
            $eonSupplier = Supplier::create([
                'name' => 'EON SE',
                'company_name' => 'EON SE',
                'supplier_number' => $uniqueNumber,
                'is_active' => true,
            ]);
            
            echo "✅ EON Supplier erstellt: {$eonSupplier->display_name}\n";
        } else {
            echo "✅ EON Supplier gefunden: {$eonSupplier->display_name}\n";
        }

        // Erstelle Test-Vertrag falls nicht vorhanden
        $testContract = $eonSupplier->contracts()
            ->where('contract_number', 'EON-TEST-001')
            ->first();

        if (!$testContract) {
            $testContract = SupplierContract::create([
                'supplier_id' => $eonSupplier->id,
                'contract_number' => 'EON-TEST-001',
                'title' => 'EON Test Vertrag für Contract Matching',
                'external_contract_number' => '231000662059', // Normalisiert ohne Leerzeichen
                'contract_recognition_1' => 'Musterstraße 123, 12345 Musterstadt',
                'status' => 'active',
                'start_date' => now(),
                'is_active' => true,
            ]);
            
            echo "✅ EON Test-Vertrag erstellt: {$testContract->contract_number}\n";
        } else {
            echo "✅ EON Test-Vertrag gefunden: {$testContract->contract_number}\n";
        }

        // Lösche bestehende EON Contract-Matching-Rules
        ContractMatchingRule::where('supplier_contract_id', $testContract->id)->delete();
        echo "🧹 Bestehende EON Contract-Matching-Rules gelöscht\n";

        // Rule 1: Contract Account → External Contract Number
        $rule1 = ContractMatchingRule::create([
            'supplier_contract_id' => $testContract->id,
            'rule_name' => 'EON Contract Account Match',
            'source_field' => 'contract_account',
            'target_field' => 'external_contract_number',
            'field_source' => 'extracted_data',
            'field_name' => 'contract_account',
            'matching_pattern' => $testContract->external_contract_number,
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'priority' => 1,
            'description' => 'Matcht Contract Account aus PDF-Extraktion mit External Contract Number im Vertrag. Normalisiert Leerzeichen automatisch.',
            'confidence_weight' => 10,
            'case_sensitive' => false,
            'normalize_whitespace' => true,
            'remove_special_chars' => false,
            'is_active' => true,
        ]);

        echo "✅ Rule 1 erstellt: {$rule1->rule_name}\n";

        // Rule 2: Consumption Site → Contract Recognition 1
        $rule2 = ContractMatchingRule::create([
            'supplier_contract_id' => $testContract->id,
            'rule_name' => 'EON Consumption Site Match',
            'source_field' => 'consumption_site',
            'target_field' => 'contract_recognition_1',
            'field_source' => 'extracted_data',
            'field_name' => 'consumption_site',
            'matching_pattern' => $testContract->contract_recognition_1,
            'match_type' => 'exact',
            'match_threshold' => 1.0,
            'priority' => 2,
            'description' => 'Matcht Consumption Site aus PDF-Extraktion mit Contract Recognition 1 im Vertrag. Normalisiert Leerzeichen und Groß-/Kleinschreibung.',
            'confidence_weight' => 10,
            'case_sensitive' => false,
            'normalize_whitespace' => true,
            'remove_special_chars' => false,
            'is_active' => true,
        ]);

        echo "✅ Rule 2 erstellt: {$rule2->rule_name}\n";

        echo "\n🎯 EON Contract-Matching-Rules erfolgreich erstellt!\n";
        echo "📋 Beide Rules müssen matchen für 100% Confidence:\n";
        echo "   1. Contract Account → External Contract Number\n";
        echo "   2. Consumption Site → Contract Recognition 1\n";
        echo "\n💡 Test-Daten:\n";
        echo "   - Contract Account: '231 000 662 059' (mit Leerzeichen)\n";
        echo "   - External Contract Number: '{$testContract->external_contract_number}' (ohne Leerzeichen)\n";
        echo "   - Consumption Site: 'Musterstraße 123, 12345 Musterstadt'\n";
        echo "   - Contract Recognition 1: '{$testContract->contract_recognition_1}'\n";
    }
}