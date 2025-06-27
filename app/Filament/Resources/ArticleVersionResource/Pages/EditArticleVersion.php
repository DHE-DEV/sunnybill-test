<?php

namespace App\Filament\Resources\ArticleVersionResource\Pages;

use App\Filament\Resources\ArticleVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticleVersion extends EditRecord
{
    protected static string $resource = ArticleVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
