<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Widgets\CustomerAddressesWidget;
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

    /**
     * Erlaube Create-Aktionen in RelationManagern auch in der View-Ansicht
     * Notwendig für DocumentsRelationManager und andere RelationManager
     */
    public function canCreateRelatedRecords(): bool
    {
        return true;
    }

    protected function getFooterWidgets(): array
    {
        return [
            //CustomerAddressesWidget::make(['customerId' => (int) $this->record->id]),
        ];
    }

    public function getFooterWidgetsColumns(): int | string | array
    {
        return 1;
    }
}