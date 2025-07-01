<?php

namespace App\Console\Commands;

use App\Models\SolarPlant;
use App\Models\SupplierContract;
use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingAllocation;
use Illuminate\Console\Command;

class CreateTestAllocations extends Command
{
    protected $signature = 'test:create-allocations {billing_id?}';
    
    protected $description = 'Erstellt Test-Aufteilungen für eine Abrechnung';

    public function handle()
    {
        $billingId = $this->argument('billing_id');
        
        if (!$billingId) {
            // Zeige verfügbare Abrechnungen
            $billings = SupplierContractBilling::with('supplierContract.supplier')
                ->latest()
                ->take(10)
                ->get();
                
            if ($billings->isEmpty()) {
                $this->error('Keine Abrechnungen gefunden.');
                return 1;
            }
            
            $this->info('Verfügbare Abrechnungen:');
            foreach ($billings as $billing) {
                $supplier = $billing->supplierContract->supplier->name ?? 'Unbekannt';
                $this->line("ID: {$billing->id} - {$billing->billing_number} - {$billing->title} ({$supplier})");
            }
            
            $billingId = $this->ask('Bitte geben Sie die Abrechnungs-ID ein');
        }
        
        $billing = SupplierContractBilling::find($billingId);
        if (!$billing) {
            $this->error('Abrechnung nicht gefunden.');
            return 1;
        }
        
        // Lösche bestehende Aufteilungen
        $billing->allocations()->delete();
        
        // Hole verfügbare Solaranlagen
        $solarPlants = SolarPlant::where('is_active', true)->take(3)->get();
        
        if ($solarPlants->isEmpty()) {
            $this->error('Keine aktiven Solaranlagen gefunden.');
            return 1;
        }
        
        $this->info("Erstelle Aufteilungen für Abrechnung: {$billing->billing_number}");
        $this->info("Gesamtbetrag: " . number_format($billing->total_amount, 2, ',', '.') . " €");
        
        $totalPercentage = 0;
        $remainingPlants = $solarPlants->count();
        
        foreach ($solarPlants as $index => $plant) {
            if ($index === $solarPlants->count() - 1) {
                // Letzte Anlage bekommt den Rest
                $percentage = 100 - $totalPercentage;
            } else {
                // Zufälliger Prozentsatz zwischen 20% und 40%
                $maxPercentage = min(40, (100 - $totalPercentage) - (($remainingPlants - 1) * 10));
                $percentage = rand(20, $maxPercentage);
            }
            
            $amount = ($billing->total_amount * $percentage) / 100;
            
            SupplierContractBillingAllocation::create([
                'supplier_contract_billing_id' => $billing->id,
                'solar_plant_id' => $plant->id,
                'percentage' => $percentage,
                'amount' => $amount,
                'notes' => "Test-Aufteilung für {$plant->name}",
                'is_active' => true,
            ]);
            
            $this->info("✓ {$plant->name}: {$percentage}% (" . number_format($amount, 2, ',', '.') . " €)");
            
            $totalPercentage += $percentage;
            $remainingPlants--;
        }
        
        $this->info("Gesamt verteilt: {$totalPercentage}%");
        $this->info('✅ Test-Aufteilungen erfolgreich erstellt!');
        
        return 0;
    }
}