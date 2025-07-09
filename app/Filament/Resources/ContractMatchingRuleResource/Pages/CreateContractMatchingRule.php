<?php

namespace App\Filament\Resources\ContractMatchingRuleResource\Pages;

use App\Filament\Resources\ContractMatchingRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContractMatchingRule extends CreateRecord
{
    protected static string $resource = ContractMatchingRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}