<?php

namespace App\Filament\Resources\GmailEmailResource\Pages;

use App\Filament\Resources\GmailEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGmailEmails extends ListRecords
{
    protected static string $resource = GmailEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_emails')
                ->label('E-Mails synchronisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    try {
                        $gmailService = new \App\Services\GmailService();
                        
                        if (!$gmailService->isConfigured()) {
                            throw new \Exception('Gmail ist nicht konfiguriert. Bitte konfigurieren Sie Gmail in den Firmeneinstellungen.');
                        }
                        
                        $stats = $gmailService->syncEmails();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('E-Mails synchronisiert')
                            ->body("Verarbeitet: {$stats['processed']}, Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}, Fehler: {$stats['errors']}")
                            ->success()
                            ->send();
                        
                        // Seite neu laden um Badge zu aktualisieren
                        return redirect()->refresh();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Synchronisation fehlgeschlagen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('test_connection')
                ->label('Verbindung testen')
                ->icon('heroicon-o-wifi')
                ->color('success')
                ->action(function () {
                    try {
                        $gmailService = new \App\Services\GmailService();
                        $result = $gmailService->testConnection();
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Verbindung erfolgreich')
                                ->body("Verbunden mit: {$result['email']}")
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception($result['error']);
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Verbindung fehlgeschlagen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
