<?php

namespace App\Filament\Resources\AppTokenResource\Pages;

use App\Filament\Resources\AppTokenResource;
use App\Models\AppToken;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAppToken extends EditRecord
{
    protected static string $resource = AppTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_permissions')
                ->label('Schnell-Berechtigungen')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Section::make('Schnell-Berechtigungen setzen')
                        ->description('Wählen Sie vordefinierte Berechtigungssets für häufige Anwendungsfälle')
                        ->schema([
                            \Filament\Forms\Components\Radio::make('permission_preset')
                                ->label('Berechtigungsset')
                                ->options([
                                    'read_only' => 'Nur Lesen - Aufgaben und Daten ansehen',
                                    'basic_user' => 'Standard-Benutzer - Lesen, Erstellen, Status ändern',
                                    'power_user' => 'Power-User - Alle Aufgaben-Rechte außer Admin',
                                    'admin' => 'Administrator - Alle Rechte',
                                    'custom' => 'Benutzerdefiniert - Keine Änderung'
                                ])
                                ->default('custom')
                                ->required()
                        ])
                ])
                ->action(function (array $data) {
                    $permissions = match($data['permission_preset']) {
                        'read_only' => ['tasks:read', 'user:profile', 'notifications:read'],
                        'basic_user' => ['tasks:read', 'tasks:create', 'tasks:status', 'tasks:notes', 'user:profile', 'notifications:read'],
                        'power_user' => ['tasks:read', 'tasks:create', 'tasks:update', 'tasks:status', 'tasks:assign', 'tasks:notes', 'tasks:documents', 'tasks:time', 'user:profile', 'notifications:read', 'notifications:create'],
                        'admin' => array_keys(AppToken::getAvailableAbilities()),
                        'custom' => null
                    };
                    
                    if ($permissions) {
                        $this->record->update(['abilities' => $permissions]);
                        
                        Notification::make()
                            ->title('Berechtigungen aktualisiert')
                            ->body('Die Berechtigungen wurden erfolgreich auf "' . match($data['permission_preset']) {
                                'read_only' => 'Nur Lesen',
                                'basic_user' => 'Standard-Benutzer', 
                                'power_user' => 'Power-User',
                                'admin' => 'Administrator'
                            } . '" gesetzt.')
                            ->success()
                            ->send();
                            
                        // Refresh the page to show updated data
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    }
                })
                ->modalWidth('lg'),

            Actions\Action::make('reset_restrictions')
                ->label('Beschränkungen zurücksetzen')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $this->record->update([
                        'restrict_customers' => false,
                        'restrict_suppliers' => false,
                        'restrict_solar_plants' => false,
                        'restrict_projects' => false,
                        'allowed_customers' => null,
                        'allowed_suppliers' => null,
                        'allowed_solar_plants' => null,
                        'allowed_projects' => null,
                    ]);
                    
                    Notification::make()
                        ->title('Beschränkungen zurückgesetzt')
                        ->body('Alle Ressourcen-Beschränkungen wurden entfernt. Der Token hat nun wieder Zugriff auf alle Ressourcen.')
                        ->success()
                        ->send();
                        
                    // Refresh the page to show updated data
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->requiresConfirmation()
                ->modalHeading('Alle Beschränkungen zurücksetzen')
                ->modalSubheading('Dies entfernt alle Ressourcen-Beschränkungen und gewährt wieder Zugriff auf alle Kunden, Lieferanten, Solaranlagen und Projekte.')
                ->modalButton('Zurücksetzen'),

            Actions\Action::make('renew_token')
                ->label('Token verlängern')
                ->icon('heroicon-o-clock')
                ->color('success')
                ->action(function () {
                    $this->record->renew();
                    
                    Notification::make()
                        ->title('Token verlängert')
                        ->body('Die Gültigkeit des Tokens wurde um 2 Jahre verlängert.')
                        ->success()
                        ->send();
                        
                    // Refresh the page to show updated data
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->requiresConfirmation()
                ->modalHeading('Token-Gültigkeit verlängern')
                ->modalSubheading('Möchten Sie die Gültigkeit dieses Tokens um 2 Jahre verlängern?'),

            Actions\Action::make('toggle_status')
                ->label(fn () => $this->record->is_active ? 'Deaktivieren' : 'Aktivieren')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->action(function () {
                    if ($this->record->is_active) {
                        $this->record->disable();
                        $message = 'Token wurde deaktiviert';
                    } else {
                        $this->record->enable();
                        $message = 'Token wurde aktiviert';
                    }
                    
                    Notification::make()
                        ->title('Status geändert')
                        ->body($message)
                        ->success()
                        ->send();
                        
                    // Refresh the page to show updated data
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                })
                ->requiresConfirmation(),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Token-Einstellungen gespeichert';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Token aktualisiert')
            ->body('Die Einstellungen und Berechtigungen des Tokens wurden erfolgreich gespeichert.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Stelle sicher, dass die Restrict-Flags korrekt gesetzt sind
        // basierend auf den vorhandenen allowed_* Arrays
        if (!empty($data['allowed_customers'])) {
            $data['restrict_customers'] = true;
        }
        if (!empty($data['allowed_suppliers'])) {
            $data['restrict_suppliers'] = true;
        }
        if (!empty($data['allowed_solar_plants'])) {
            $data['restrict_solar_plants'] = true;
        }
        if (!empty($data['allowed_projects'])) {
            $data['restrict_projects'] = true;
        }

        // Teile die abilities in die verschiedenen Kategorien auf
        $abilities = $data['abilities'] ?? [];
        
        // Aufgaben-Verwaltung
        $taskManagementAbilities = ['tasks:read', 'tasks:create', 'tasks:update', 'tasks:delete'];
        $data['task_management_abilities'] = array_intersect($abilities, $taskManagementAbilities);
        
        // Aufgaben-Aktionen
        $taskActionsAbilities = ['tasks:assign', 'tasks:status', 'tasks:notes', 'tasks:documents', 'tasks:time'];
        $data['task_actions_abilities'] = array_intersect($abilities, $taskActionsAbilities);
        
        // Benutzer-Berechtigungen
        $userAbilities = ['user:profile'];
        $data['user_abilities'] = array_intersect($abilities, $userAbilities);
        
        // Benachrichtigungs-Berechtigungen
        $notificationAbilities = ['notifications:read', 'notifications:create'];
        $data['notification_abilities'] = array_intersect($abilities, $notificationAbilities);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Bereinige die allowed_* Arrays wenn restrict_* false ist
        if (!($data['restrict_customers'] ?? false)) {
            $data['allowed_customers'] = null;
        }
        if (!($data['restrict_suppliers'] ?? false)) {
            $data['allowed_suppliers'] = null;
        }
        if (!($data['restrict_solar_plants'] ?? false)) {
            $data['allowed_solar_plants'] = null;
        }
        if (!($data['restrict_projects'] ?? false)) {
            $data['allowed_projects'] = null;
        }

        return $data;
    }
}
