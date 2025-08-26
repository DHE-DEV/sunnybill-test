<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Dispatch ein Event um die Versionshistorie zu aktualisieren
        $this->dispatch('refresh-versions-table');
    }

    public function getTitle(): string
    {
        $title = 'Artikel bearbeiten';
        
        if ($this->record && $this->record->isContractBound()) {
            $title .= ' (Vertragsgebundener Artikel)';
        }
        
        return $title;
    }
}
