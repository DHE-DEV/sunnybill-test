<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSolarPlantBilling extends EditRecord
{
    protected static string $resource = SolarPlantBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Wenn die Abrechnung storniert ist, zur View-Seite umleiten
        if ($this->record->status === 'cancelled') {
            Notification::make()
                ->title('Abrechnung storniert')
                ->body('Diese Abrechnung wurde storniert und kann nicht mehr bearbeitet werden.')
                ->warning()
                ->send();

            $this->redirect(SolarPlantBillingResource::getUrl('view', ['record' => $this->record]));
        }

        // Wenn die Abrechnung finalisiert, versendet oder bezahlt ist, zur View-Seite umleiten
        if (in_array($this->record->status, ['finalized', 'sent', 'paid'])) {
            Notification::make()
                ->title('Abrechnung nicht bearbeitbar')
                ->body('Diese Abrechnung wurde bereits finalisiert und kann nicht mehr bearbeitet werden. Sie können nur noch den Status ändern.')
                ->warning()
                ->send();

            $this->redirect(SolarPlantBillingResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Verhindere Speichern, wenn storniert
        if ($this->record->status === 'cancelled') {
            Notification::make()
                ->title('Speichern nicht möglich')
                ->body('Stornierte Abrechnungen können nicht bearbeitet werden.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Wenn Status auf storniert geändert wird, setze Stornierungsdatum und -grund
        if (isset($data['status']) && $data['status'] === 'cancelled' && $this->record->status !== 'cancelled') {
            $data['cancellation_date'] = now()->toDateString();
            $data['cancellation_reason'] = $data['cancellation_reason_temp'] ?? null;
        }

        // Entferne temporäres Feld
        unset($data['cancellation_reason_temp']);

        // Berechne Nettobetrag
        $data['net_amount'] = ($data['total_costs'] ?? 0) - ($data['total_credits'] ?? 0);

        return $data;
    }
}