<?php

namespace App\Filament\Resources\CustomerMonthlyCreditResource\Pages;

use App\Filament\Resources\CustomerMonthlyCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCustomerMonthlyCredits extends ListRecords
{
    protected static string $resource = CustomerMonthlyCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Keine Create-Action, da Gutschriften automatisch erstellt werden
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Alle Gutschriften')
                ->badge(fn () => $this->getModel()::count()),
            'current_month' => Tab::make('Aktueller Monat')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('month', now()->month)->whereYear('month', now()->year))
                ->badge(fn () => $this->getModel()::whereMonth('month', now()->month)->whereYear('month', now()->year)->count()),
            'current_year' => Tab::make('Aktuelles Jahr')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereYear('month', now()->year))
                ->badge(fn () => $this->getModel()::whereYear('month', now()->year)->count()),
            'high_credits' => Tab::make('Hohe Gutschriften (≥ €50)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('total_credit', '>=', 50))
                ->badge(fn () => $this->getModel()::where('total_credit', '>=', 50)->count()),
        ];
    }
}