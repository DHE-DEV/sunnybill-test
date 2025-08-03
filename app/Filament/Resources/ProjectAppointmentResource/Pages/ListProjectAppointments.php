<?php

namespace App\Filament\Resources\ProjectAppointmentResource\Pages;

use App\Filament\Resources\ProjectAppointmentResource;
use App\Traits\HasPersistentTableState;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectAppointments extends ListRecords
{
    use HasPersistentTableState;

    protected static string $resource = ProjectAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
