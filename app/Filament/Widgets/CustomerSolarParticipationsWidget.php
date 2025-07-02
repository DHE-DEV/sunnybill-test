<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource\RelationManagers\SolarParticipationsRelationManager;
use Filament\Widgets\Widget;

class CustomerSolarParticipationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-relation-manager';
    
    public ?int $customerId = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Solar-Beteiligungen';
    
    protected static ?string $icon = 'heroicon-o-sun';

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function getRelationManagerClass(): string
    {
        return SolarParticipationsRelationManager::class;
    }

    public function getOwnerRecord()
    {
        return \App\Models\Customer::find($this->customerId);
    }

    public function getHeading(): string
    {
        $count = $this->getOwnerRecord()?->solarParticipations()->count() ?? 0;
        return static::$heading . " ({$count})";
    }
}