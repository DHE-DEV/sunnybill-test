<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Notifications\NewUserPasswordNotification;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Benutzer erstellt')
            ->body('Der neue Benutzer wurde erfolgreich erstellt und eine E-Mail-Bestätigung wurde gesendet.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Setze Standard-Werte falls nicht angegeben
        $data['is_active'] = $data['is_active'] ?? true;
        $data['role'] = $data['role'] ?? 'user';
        
        // Generiere automatisch ein zufälliges Passwort falls keines angegeben
        if (empty($data['password'])) {
            $data['temporary_password'] = User::generateRandomPassword();
            $data['password'] = Hash::make($data['temporary_password']);
        }
        
        // Setze password_change_required auf true für neue Benutzer
        $data['password_change_required'] = true;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        $formData = $this->form->getState();
        $temporaryPassword = $formData['temporary_password'] ?? null;
        
        if ($user && $user->email) {
            try {
                // Sende E-Mail-Verifikation mit temporärem Passwort
                if (!$user->hasVerifiedEmail()) {
                    $user->sendEmailVerificationNotification($temporaryPassword);
                    
                    Notification::make()
                        ->success()
                        ->title('Benutzer erstellt und E-Mail-Verifikation gesendet')
                        ->body("Der Benutzer wurde erstellt und eine E-Mail-Verifikation mit den Anmeldedaten wurde an {$user->email} gesendet.")
                        ->send();
                }
                    
            } catch (\Exception $e) {
                // Fehlerbehandlung falls E-Mail nicht gesendet werden kann
                Notification::make()
                    ->warning()
                    ->title('E-Mail-Versand fehlgeschlagen')
                    ->body("Der Benutzer wurde erstellt, aber die E-Mail-Verifikation konnte nicht gesendet werden. Fehler: " . $e->getMessage())
                    ->send();
            }
        }
    }
}
