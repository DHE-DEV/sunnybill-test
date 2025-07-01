<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplierContract;
use App\Models\SolarPlant;

class TestAllocationForm extends Command
{
    protected $signature = 'test:allocation-form';
    protected $description = 'Test the allocation form logic';

    public function handle()
    {
        $this->info('Testing Allocation Form Logic...');
        
        try {
            // Test 1: Prüfe verfügbare Verträge
            $this->info('1. Testing available contracts...');
            $contracts = SupplierContract::with('supplier')->get();
            $this->info("Found {$contracts->count()} contracts");
            
            foreach ($contracts->take(3) as $contract) {
                $supplierName = $contract->supplier?->name ?? 'Unbekannt';
                $this->line("  - {$contract->contract_number} - {$contract->title} ({$supplierName})");
            }
            
            // Test 2: Prüfe Solaranlagen für ersten Vertrag
            if ($contracts->count() > 0) {
                $firstContract = $contracts->first();
                $this->info("\n2. Testing solar plants for contract: {$firstContract->contract_number}");
                
                // Simuliere die Logik aus dem Formular
                $assignedPlants = $firstContract->activeSolarPlants();
                $this->info("Assigned plants query created");
                
                if ($assignedPlants->count() > 0) {
                    $plants = $assignedPlants->get()
                        ->filter(fn($plant) => !empty($plant->name))
                        ->pluck('name', 'id')
                        ->toArray();
                    
                    $this->info("Found {$assignedPlants->count()} assigned plants");
                    foreach ($plants as $id => $name) {
                        $this->line("  - ID: {$id}, Name: {$name}");
                    }
                } else {
                    // Fallback: Alle aktiven Solaranlagen
                    $allActivePlants = SolarPlant::where('is_active', true)
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->orderBy('name')
                        ->get();
                    
                    $this->info("No assigned plants, using fallback. Found {$allActivePlants->count()} active plants");
                    foreach ($allActivePlants->take(5) as $plant) {
                        $this->line("  - ID: {$plant->id}, Name: {$plant->name}");
                    }
                }
            }
            
            // Test 3: Prüfe alle Solaranlagen auf null-Namen
            $this->info("\n3. Checking for plants with null/empty names...");
            $plantsWithNullNames = SolarPlant::whereNull('name')
                ->orWhere('name', '')
                ->get();
            
            if ($plantsWithNullNames->count() > 0) {
                $this->warn("Found {$plantsWithNullNames->count()} plants with null/empty names:");
                foreach ($plantsWithNullNames as $plant) {
                    $name = $plant->name ?? 'NULL';
                    $this->line("  - ID: {$plant->id}, Name: '{$name}', Active: " . ($plant->is_active ? 'Yes' : 'No'));
                }
            } else {
                $this->info("All plants have valid names");
            }
            
            $this->info("\n✅ Test completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}