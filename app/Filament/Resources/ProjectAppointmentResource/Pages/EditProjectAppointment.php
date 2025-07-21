<?php

namespace App\Filament\Resources\ProjectAppointmentResource\Pages;

use App\Filament\Resources\ProjectAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectAppointment extends EditRecord
{
    protected static string $resource = ProjectAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
