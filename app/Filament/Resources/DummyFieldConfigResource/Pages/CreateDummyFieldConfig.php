<?php

namespace App\Filament\Resources\DummyFieldConfigResource\Pages;

use App\Filament\Resources\DummyFieldConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDummyFieldConfig extends CreateRecord
{
    protected static string $resource = DummyFieldConfigResource::class;
}
