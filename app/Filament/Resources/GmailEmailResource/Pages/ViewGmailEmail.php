<?php

namespace App\Filament\Resources\GmailEmailResource\Pages;

use App\Filament\Resources\GmailEmailResource;
use App\Models\GmailEmail;
use App\Services\GmailService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewGmailEmail extends ViewRecord
{
    protected static string $resource = GmailEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_read')
                ->label('Als gelesen markieren')
                ->icon('heroicon-o-envelope-open')
                ->color('success')
                ->visible(fn () => !$this->record->is_read)
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->markAsRead($this->record->gmail_id)) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('E-Mail als gelesen markiert')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Markieren als gelesen');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('mark_unread')
                ->label('Als ungelesen markieren')
                ->icon('heroicon-s-envelope')
                ->color('warning')
                ->visible(fn () => $this->record->is_read)
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->markAsUnread($this->record->gmail_id)) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('E-Mail als ungelesen markiert')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Markieren als ungelesen');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('star')
                ->label('Favorit hinzufügen')
                ->icon('heroicon-s-star')
                ->color('warning')
                ->visible(fn () => !$this->record->is_starred)
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->addLabels($this->record->gmail_id, ['STARRED'])) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('Favorit hinzugefügt')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Hinzufügen des Favoriten');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('unstar')
                ->label('Favorit entfernen')
                ->icon('heroicon-o-star')
                ->color('gray')
                ->visible(fn () => $this->record->is_starred)
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->removeLabels($this->record->gmail_id, ['STARRED'])) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('Favorit entfernt')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Entfernen des Favoriten');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('move_to_trash')
                ->label('In Papierkorb verschieben')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => !$this->record->is_trash)
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->moveToTrash($this->record->gmail_id)) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('E-Mail in Papierkorb verschoben')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Verschieben in den Papierkorb');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('restore_from_trash')
                ->label('Aus Papierkorb wiederherstellen')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn () => $this->record->is_trash)
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        if ($gmailService->restoreFromTrash($this->record->gmail_id)) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('E-Mail wiederhergestellt')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Fehler beim Wiederherstellen');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('download_pdf_attachments')
                ->label('PDF-Anhänge herunterladen')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn () => $this->record->hasPdfAttachments())
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        $pdfAttachments = $this->record->getPdfAttachments();
                        
                        if (empty($pdfAttachments)) {
                            throw new \Exception('Keine PDF-Anhänge zum Herunterladen gefunden');
                        }
                        
                        $zipPath = $gmailService->downloadPdfAttachments($this->record->gmail_id, $pdfAttachments);
                        
                        if ($zipPath && file_exists($zipPath)) {
                            $fileName = basename($zipPath);
                            
                            // Download-Response erstellen
                            return response()->download($zipPath, $fileName)->deleteFileAfterSend(true);
                        } else {
                            throw new \Exception('ZIP-Datei konnte nicht erstellt werden');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Herunterladen der PDF-Anhänge')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('secondary')
                ->action(function () {
                    try {
                        $gmailService = new GmailService();
                        $updated = $gmailService->syncSingleEmail($this->record->gmail_id);
                        
                        if ($updated) {
                            $this->record->refresh();
                            Notification::make()
                                ->title('E-Mail aktualisiert')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('E-Mail konnte nicht aktualisiert werden');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Aktualisieren')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Automatisch als gelesen markieren beim Öffnen
        if (!$this->record->is_read) {
            try {
                $gmailService = new GmailService();
                $gmailService->markAsRead($this->record->gmail_id);
                $this->record->refresh();
            } catch (\Exception $e) {
                // Fehler beim automatischen Markieren ignorieren
            }
        }

        return $data;
    }
}
