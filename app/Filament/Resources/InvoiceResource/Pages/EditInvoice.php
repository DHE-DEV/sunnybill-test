<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\InvoiceVersion;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Wenn die Rechnung storniert ist, Warnung anzeigen
        if ($this->record->status === 'canceled') {
            \Filament\Notifications\Notification::make()
                ->title('Rechnung storniert')
                ->body('Diese Rechnung wurde storniert. Sie können nur noch den Status ändern.')
                ->warning()
                ->send();
        }

        // Wenn die Rechnung versendet oder bezahlt ist, Warnung anzeigen
        if (in_array($this->record->status, ['sent', 'paid'])) {
            \Filament\Notifications\Notification::make()
                ->title('Rechnung nicht bearbeitbar')
                ->body('Diese Rechnung wurde bereits versendet/bezahlt. Sie können nur noch den Status ändern.')
                ->warning()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->canBeEdited()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Wenn die Rechnung storniert ist, erlaube keine Änderungen
        if ($this->record->status === 'canceled') {
            \Filament\Notifications\Notification::make()
                ->title('Speichern nicht möglich')
                ->body('Stornierte Rechnungen können nicht bearbeitet werden.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Wenn die Rechnung nicht im Entwurf ist, nur Status-Änderungen erlauben
        if ($this->record->status !== 'draft') {
            // Behalte nur den Status, alle anderen Felder bleiben unverändert
            return [
                'status' => $data['status'] ?? $this->record->status,
            ];
        }

        return $data;
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
