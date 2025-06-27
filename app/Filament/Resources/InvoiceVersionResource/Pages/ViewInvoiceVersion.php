<?php

namespace App\Filament\Resources\InvoiceVersionResource\Pages;

use App\Filament\Resources\InvoiceVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceVersion extends ViewRecord
{
    protected static string $resource = InvoiceVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_copy')
                ->label('Kopie erstellen')
                ->icon('heroicon-o-document-duplicate')
                ->action(function () {
                    $newInvoice = $this->record->createInvoiceCopy();
                    
                    $this->redirect(route('filament.admin.resources.invoices.edit', $newInvoice));
                })
                ->requiresConfirmation()
                ->modalHeading('Rechnungskopie erstellen')
                ->modalDescription('Möchten Sie eine neue Rechnung basierend auf dieser Version erstellen?'),
            
            Actions\Action::make('download_pdf')
                ->label('PDF herunterladen')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => route('invoice.pdf.version', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}