<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\InvoiceVersion;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    

    protected function afterSave(): void
    {
        $invoice = $this->record->fresh(['customer', 'items.article', 'items.articleVersion', 'items.taxRateVersion']);
        
        if (InvoiceVersion::hasChangedSinceLastVersion($invoice)) {
            // Bestimme den Änderungsgrund basierend auf der Art der Änderung
            $changeReason = $this->determineChangeReason($invoice);
            
            // Erstelle nur dann eine neue Version, wenn sich etwas geändert hat
            InvoiceVersion::createVersion(
                $invoice,
                [], // Änderungen werden automatisch erkannt
                $changeReason,
                auth()->user()?->name ?? 'System'
            );
            
            // Zeige Erfolgs-Notification
            \Filament\Notifications\Notification::make()
                ->title('Version erstellt')
                ->body("Neue Version erstellt: {$changeReason}")
                ->success()
                ->send();
        }
        
        // Weiterleitung zur Rechnungsliste (immer nach dem Speichern)
        $this->redirect('/admin/invoices', navigate: false);
    }
    
    /**
     * Bestimme den Änderungsgrund basierend auf der Art der Änderung
     */
    private function determineChangeReason($invoice): string
    {
        $lastVersion = InvoiceVersion::getCurrentVersion($invoice);
        
        if (!$lastVersion) {
            return 'Rechnung erstellt';
        }
        
        // Vergleiche Item-Anzahl
        $currentItemsCount = $invoice->items->count();
        $lastItemsCount = count($lastVersion->items_data ?? []);
        
        if ($currentItemsCount > $lastItemsCount) {
            return 'Rechnungsposten hinzugefügt';
        } elseif ($currentItemsCount < $lastItemsCount) {
            return 'Rechnungsposten gelöscht';
        }
        
        // Prüfe ob sich Rechnungsdaten geändert haben
        $currentInvoiceData = [
            'status' => $invoice->status,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
        ];
        
        $lastInvoiceData = [
            'status' => $lastVersion->invoice_data['status'] ?? null,
            'total' => $lastVersion->invoice_data['total'] ?? null,
            'due_date' => $lastVersion->invoice_data['due_date'] ?? null,
        ];
        
        if ($currentInvoiceData !== $lastInvoiceData) {
            return 'Rechnungsdaten geändert';
        }
        
        // Prüfe ob sich Items geändert haben
        return 'Rechnungsposten geändert';
    }
    
}
