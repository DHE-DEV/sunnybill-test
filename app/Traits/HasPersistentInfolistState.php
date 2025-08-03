<?php

namespace App\Traits;

use App\Models\UserTablePreference;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

trait HasPersistentInfolistState
{
    protected function getInfolistTableName(): string
    {
        return class_basename(static::$resource::getModel()) . '_view';
    }

    protected function saveInfolistState(array $state): void
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $tableName = $this->getInfolistTableName();

        UserTablePreference::saveInfolistState($userId, $tableName, $state);
    }

    protected function loadInfolistState(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $userId = Auth::id();
        $tableName = $this->getInfolistTableName();

        return UserTablePreference::getInfolistState($userId, $tableName) ?? [];
    }

    protected function getHeaderActions(): array
    {
        $actions = parent::getHeaderActions() ?? [];
        
        // Füge Action zum Zurücksetzen der Infolist-Zustände hinzu
        $actions[] = \Filament\Actions\Action::make('reset_infolist_state')
            ->label('Abschnitte zurücksetzen')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->tooltip('Alle auf-/zugeklappten Zustände zurücksetzen')
            ->action(function () {
                if (Auth::check()) {
                    $userId = Auth::id();
                    $tableName = $this->getInfolistTableName();
                    
                    UserTablePreference::saveInfolistState($userId, $tableName, []);
                    
                    Notification::make()
                        ->title('Abschnitte zurückgesetzt')
                        ->body('Alle Infolist-Abschnitte wurden auf den Standardzustand zurückgesetzt.')
                        ->success()
                        ->send();
                        
                    // Seite neu laden um Änderungen anzuzeigen
                    $this->redirect(request()->url());
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Abschnitte zurücksetzen')
            ->modalDescription('Möchten Sie alle auf-/zugeklappten Zustände der Infolist-Abschnitte zurücksetzen?')
            ->modalSubmitActionLabel('Zurücksetzen');
            
        return $actions;
    }

    public function mount($record): void
    {
        parent::mount($record);
        
        // Lade gespeicherte Infolist-Zustände
        $this->infolistState = $this->loadInfolistState();
    }

    protected array $infolistState = [];
}
