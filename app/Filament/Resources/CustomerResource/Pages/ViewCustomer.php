<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Widgets\CustomerDocumentsTableWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CustomerDocumentsTableWidget::make(['customerId' => (int) $this->record->id]),
        ];
    }

    public function getFooterWidgetsColumns(): int | string | array
    {
        return 1;
    }
}