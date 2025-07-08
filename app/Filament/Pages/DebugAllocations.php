<?php

namespace App\Filament\Pages;

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingAllocation;
use App\Models\SolarPlant;
use Filament\Pages\Page;

class DebugAllocations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    
    protected static string $view = 'filament.pages.debug-allocations';
    
    protected static ?string $title = 'Debug Kostenträger-Aufteilungen';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 999;
    
    public function mount()
    {
        // Debug-Informationen sammeln
        $this->debugInfo = [
            'billings_count' => SupplierContractBilling::count(),
            'allocations_count' => SupplierContractBillingAllocation::count(),
            'solar_plants_count' => SolarPlant::where('is_active', true)->count(),
            'recent_billing' => SupplierContractBilling::with(['allocations.solarPlant', 'supplierContract'])
                ->latest()
                ->first(),
        ];
    }
    
    public $debugInfo = [];
}