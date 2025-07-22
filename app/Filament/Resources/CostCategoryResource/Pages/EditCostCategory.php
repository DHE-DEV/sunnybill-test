<?php

namespace App\Filament\Resources\CostCategoryResource\Pages;

use App\Filament\Resources\CostCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCostCategory extends EditRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->costs()->exists()) {
                        $action->cancel();
                        $action->failure();
                        $action->failureNotificationTitle('Kategorie kann nicht gelöscht werden, da sie noch Kosten enthält');
                        $action->sendFailureNotification();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}