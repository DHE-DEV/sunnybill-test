<?php

namespace App\Filament\Resources\TaskTypeResource\Pages;

use App\Filament\Resources\TaskTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskType extends EditRecord
{
    protected static string $resource = TaskTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function () {
                    if (!$this->record->canBeDeleted()) {
                        throw new \Exception('Aufgabentyp kann nicht gelöscht werden, da noch Aufgaben zugeordnet sind.');
                    }
                }),
        ];
    }
}
