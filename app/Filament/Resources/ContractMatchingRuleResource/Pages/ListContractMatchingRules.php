<?php

namespace App\Filament\Resources\ContractMatchingRuleResource\Pages;

use App\Filament\Resources\ContractMatchingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractMatchingRules extends ListRecords
{
    protected static string $resource = ContractMatchingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Matching-Regel'),
        ];
    }
}