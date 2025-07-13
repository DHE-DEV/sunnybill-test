<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\LexofficeService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // Automatische Lexoffice-Synchronisation wenn Lexoffice-ID vorhanden
        if ($this->record->lexoffice_id) {
            try {
                $lexofficeService = new LexofficeService();
                $syncResult = $lexofficeService->syncCustomer($this->record);
                
                if ($syncResult['success']) {
                    Notification::make()
                        ->title('Kunde gespeichert und synchronisiert')
                        ->body('Die Kundendaten wurden erfolgreich gespeichert und automatisch in Lexoffice synchronisiert.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Kunde gespeichert')
                        ->body('Die Kundendaten wurden erfolgreich gespeichert. Lexoffice-Synchronisation fehlgeschlagen: ' . $syncResult['error'])
                        ->warning()
                        ->send();
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Kunde gespeichert')
                    ->body('Die Kundendaten wurden erfolgreich gespeichert. Lexoffice-Synchronisation fehlgeschlagen: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        } else {
            // Standard-Benachrichtigung wenn keine Lexoffice-ID vorhanden
            Notification::make()
                ->title('Kunde gespeichert')
                ->body('Die Kundendaten wurden erfolgreich gespeichert.')
                ->success()
                ->send();
        }
    }
}
