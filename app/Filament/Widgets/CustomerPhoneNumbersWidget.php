<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource\RelationManagers\PhoneNumbersRelationManager;
use Filament\Widgets\Widget;

class CustomerPhoneNumbersWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-relation-manager';
    
    public ?int $customerId = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Telefonnummern';
    
    protected static ?string $icon = 'heroicon-o-phone';

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function getRelationManagerClass(): string
    {
        return PhoneNumbersRelationManager::class;
    }

    public function getOwnerRecord()
    {
        return \App\Models\Customer::find($this->customerId);
    }

    public function getHeading(): string
    {
        $count = $this->getOwnerRecord()?->phoneNumbers()->count() ?? 0;
        return static::$heading . " ({$count})";
    }
}