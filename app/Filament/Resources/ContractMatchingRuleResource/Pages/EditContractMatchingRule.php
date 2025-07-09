<?php

namespace App\Filament\Resources\ContractMatchingRuleResource\Pages;

use App\Filament\Resources\ContractMatchingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractMatchingRule extends EditRecord
{
    protected static string $resource = ContractMatchingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Anzeigen'),
            Actions\DeleteAction::make()
                ->label('Löschen'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}