<?php

namespace App\Filament\Resources\TaskTypeResource\Pages;

use App\Filament\Resources\TaskTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTaskType extends ViewRecord
{
    protected static string $resource = TaskTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}