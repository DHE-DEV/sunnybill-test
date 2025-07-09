<?php

namespace App\Filament\Resources\ContractMatchingRuleResource\Pages;

use App\Filament\Resources\ContractMatchingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContractMatchingRule extends ViewRecord
{
    protected static string $resource = ContractMatchingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Bearbeiten'),
        ];
    }
}