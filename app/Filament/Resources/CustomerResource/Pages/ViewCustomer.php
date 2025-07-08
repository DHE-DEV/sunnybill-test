<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Widgets\CustomerAddressesWidget;
use App\Services\LexofficeService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('fetch_lexware_data')
                ->label('Lexware-Daten abrufen')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->action(function () {
                    $service = new LexofficeService();
                    $result = $service->fetchAndStoreLexwareData($this->record);
                    
                    if ($result['success']) {
                        $message = $result['message'] ?? 'Lexware-Daten erfolgreich abgerufen';
                        $version = $result['version'] ?? 'Unbekannt';
                        
                        Notification::make()
                            ->title('Lexware-Daten abgerufen')
                            ->body("{$message}\nVersion: {$version}")
                            ->success()
                            ->send();
                            
                        // Seite neu laden um aktualisierte Daten anzuzeigen
                        return redirect()->to(static::getUrl(['record' => $this->record]));
                    } else {
                        Notification::make()
                            ->title('Fehler beim Abrufen der Lexware-Daten')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Lexware-Daten von Lexoffice abrufen')
                ->modalDescription('Möchten Sie die aktuellen Lexware-Daten von Lexoffice abrufen und lokal speichern?')
                ->modalSubmitActionLabel('Daten abrufen')
                ->visible(fn () => $this->record->lexoffice_id && config('services.lexoffice.api_key')),
            
            Actions\Action::make('sync_with_lexoffice')
                ->label('Mit Lexoffice synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    $service = new LexofficeService();
                    $result = $service->syncCustomer($this->record);
                    
                    if ($result['success']) {
                        $message = $result['message'] ?? 'Synchronisation erfolgreich';
                        
                        Notification::make()
                            ->title('Synchronisation erfolgreich')
                            ->body($message)
                            ->success()
                            ->send();
                            
                        // Seite neu laden um aktualisierte Daten anzuzeigen
                        return redirect()->to(static::getUrl(['record' => $this->record]));
                    } elseif (isset($result['conflict']) && $result['conflict']) {
                        // Synchronisationskonflikt
                        Notification::make()
                            ->title('Synchronisationskonflikt erkannt')
                            ->body("Sowohl lokale als auch Lexoffice-Daten wurden geändert.\n" .
                                   "Lokal: {$result['local_updated']}\n" .
                                   "Lexoffice: {$result['lexoffice_updated']}\n" .
                                   "Letzte Sync: {$result['last_synced']}")
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Synchronisation fehlgeschlagen')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Kunde mit Lexoffice synchronisieren')
                ->modalDescription(function () {
                    if ($this->record->lexoffice_id) {
                        return 'Möchten Sie die Kundendaten in Lexoffice aktualisieren?';
                    }
                    return 'Möchten Sie diesen Kunden in Lexoffice erstellen?';
                })
                ->modalSubmitActionLabel('Synchronisieren')
                ->visible(fn () => config('services.lexoffice.api_key')), // Nur anzeigen wenn API Key konfiguriert ist
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
