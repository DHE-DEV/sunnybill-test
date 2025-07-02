<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource\RelationManagers\MonthlyCreditsRelationManager;
use Filament\Widgets\Widget;

class CustomerMonthlyCreditsWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-relation-manager';
    
    public ?int $customerId = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Monatliche Gutschriften';
    
    protected static ?string $icon = 'heroicon-o-banknotes';

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function getRelationManagerClass(): string
    {
        return MonthlyCreditsRelationManager::class;
    }

    public function getOwnerRecord()
    {
        return \App\Models\Customer::find($this->customerId);
    }

    public function getHeading(): string
    {
        $count = $this->getOwnerRecord()?->monthlyCredits()->count() ?? 0;
        return static::$heading . " ({$count})";
    }
}