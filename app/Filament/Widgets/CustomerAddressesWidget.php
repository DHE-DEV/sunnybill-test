<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use Filament\Widgets\Widget;

class CustomerAddressesWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-relation-manager';
    
    public ?int $customerId = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Adressen';
    
    protected static ?string $icon = 'heroicon-o-map-pin';

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function getRelationManagerClass(): string
    {
        return AddressesRelationManager::class;
    }

    public function getOwnerRecord()
    {
        return \App\Models\Customer::find($this->customerId);
    }

    public function getHeading(): string
    {
        $count = $this->getOwnerRecord()?->addresses()->count() ?? 0;
        return static::$heading . " ({$count})";
    }
}