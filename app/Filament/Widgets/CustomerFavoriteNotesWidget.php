<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CustomerResource\RelationManagers\FavoriteNotesRelationManager;
use Filament\Widgets\Widget;

class CustomerFavoriteNotesWidget extends Widget
{
    protected static string $view = 'filament.widgets.customer-relation-manager';
    
    public ?int $customerId = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Favoriten-Notizen';
    
    protected static ?string $icon = 'heroicon-o-star';

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function getRelationManagerClass(): string
    {
        return FavoriteNotesRelationManager::class;
    }

    public function getOwnerRecord()
    {
        return \App\Models\Customer::find($this->customerId);
    }

    public function getHeading(): string
    {
        $count = $this->getOwnerRecord()?->favoriteNotes()->count() ?? 0;
        return static::$heading . " ({$count})";
    }
}