<?php

namespace App\Filament\Resources\ProjectAppointmentResource\Pages;

use App\Filament\Resources\ProjectAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectAppointment extends CreateRecord
{
    protected static string $resource = ProjectAppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
