<?php

namespace App\Filament\Resources\CostCategoryResource\Pages;

use App\Filament\Resources\CostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCostCategory extends CreateRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}