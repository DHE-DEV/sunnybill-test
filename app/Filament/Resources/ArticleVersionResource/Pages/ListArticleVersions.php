<?php

namespace App\Filament\Resources\ArticleVersionResource\Pages;

use App\Filament\Resources\ArticleVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleVersions extends ListRecords
{
    protected static string $resource = ArticleVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
