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
    
    protected ?string $temporaryPassword = null;

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
        
        // Speichere das ursprüngliche Passwort für das temporäre Passwort
        $this->temporaryPassword = $data['tmp_p'] ?? User::generateRandomPassword(12);

        // Falls kein Passwort eingegeben wurde, verwende das generierte
        if (empty($data['password'])) {
            $data['password'] = $this->temporaryPassword;
        }
        
        // Setze password_change_required auf true für neue Benutzer
        $data['password_change_required'] = true;
        
        // Entferne temporary_password aus den Daten, um automatisches Hashing zu vermeiden
        unset($data['temporary_password']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        if ($user && $user->email && $this->temporaryPassword) {
            // Setze das temporäre Passwort korrekt mit der setTemporaryPassword() Methode
            $user->setTemporaryPassword($this->temporaryPassword);
            
            try {
                // Sende E-Mail-Verifikation mit temporärem Passwort
                if (!$user->hasVerifiedEmail()) {
                    $user->sendEmailVerificationNotification($this->temporaryPassword);
                    
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
