<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplierContract;
use App\Models\SolarPlant;

class TestFilamentSelect extends Command
{
    protected $signature = 'test:filament-select';
    protected $description = 'Test Filament Select component logic for null values';

    public function handle()
    {
        $this->info('Testing Filament Select Logic...');
        
        try {
            // Test 1: Simuliere die options() Funktion
            $this->info('1. Testing options() function simulation...');
            $contracts = SupplierContract::with('supplier')->get();
            
            foreach ($contracts as $contract) {
                $this->info("Testing contract: {$contract->contract_number}");
                
                // Simuliere die Logik aus dem Formular
                $assignedPlants = $contract->activeSolarPlants();
                
                if ($assignedPlants->count() > 0) {
                    $options = $assignedPlants->get()
                        ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                        ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                        ->toArray();
                    
                    $this->info("  Assigned plants options:");
                    foreach ($options as $id => $name) {
                        $this->line("    - ID: {$id}, Name: '{$name}' (Type: " . gettype($name) . ")");
                    }
                } else {
                    $options = SolarPlant::where('is_active', true)
                        ->whereNotNull('name')
                        ->where('name', '!=', '')
                        ->orderBy('name')
                        ->get()
                        ->filter(fn($plant) => !empty($plant->name) && is_string($plant->name))
                        ->mapWithKeys(fn($plant) => [$plant->id => (string) $plant->name])
                        ->toArray();
                    
                    $this->info("  Fallback plants options:");
                    foreach ($options as $id => $name) {
                        $this->line("    - ID: {$id}, Name: '{$name}' (Type: " . gettype($name) . ")");
                    }
                }
                
                // Test 2: Simuliere isOptionDisabled Logik
                $this->info("  Testing isOptionDisabled simulation:");
                foreach ($options as $value => $label) {
                    $isDisabled = empty($label) || !is_string($label);
                    $status = $isDisabled ? 'DISABLED' : 'ENABLED';
                    $this->line("    - Value: {$value}, Label: '{$label}', Status: {$status}");
                }
            }
            
            // Test 3: Prüfe auf problematische Daten
            $this->info("\n2. Checking for problematic data...");
            $allPlants = SolarPlant::all();
            $problematicPlants = $allPlants->filter(function($plant) {
                return is_null($plant->name) || 
                       $plant->name === '' || 
                       !is_string($plant->name);
            });
            
            if ($problematicPlants->count() > 0) {
                $this->warn("Found {$problematicPlants->count()} problematic plants:");
                foreach ($problematicPlants as $plant) {
                    $nameType = gettype($plant->name);
                    $nameValue = var_export($plant->name, true);
                    $this->line("  - ID: {$plant->id}, Name: {$nameValue} (Type: {$nameType})");
                }
            } else {
                $this->info("No problematic plants found - all names are valid strings");
            }
            
            $this->info("\n✅ Filament Select test completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}