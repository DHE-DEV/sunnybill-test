<?php

namespace App\Filament\Resources\ProjectMilestoneResource\Pages;

use App\Filament\Resources\ProjectMilestoneResource;
use App\Traits\HasPersistentTableState;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectMilestones extends ListRecords
{
    use HasPersistentTableState;

    protected static string $resource = ProjectMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
