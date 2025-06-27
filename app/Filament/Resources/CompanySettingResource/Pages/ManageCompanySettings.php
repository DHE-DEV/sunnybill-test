<?php

namespace App\Filament\Resources\CompanySettingResource\Pages;

use App\Filament\Resources\CompanySettingResource;
use App\Models\CompanySetting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;

class ManageCompanySettings extends ManageRecords
{
    protected static string $resource = CompanySettingResource::class;

    protected function getHeaderActions(): array
    {
        $settings = CompanySetting::first();
        
        if (!$settings) {
            return [
                Actions\CreateAction::make()
                    ->label('Firmeneinstellungen erstellen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('screen')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Stelle sicher, dass es nur eine Einstellung gibt
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Firmeneinstellungen erstellt')
                            ->body('Die Firmeneinstellungen wurden erfolgreich erstellt.')
                            ->success()
                            ->send();
                    }),
            ];
        }

        return [
            // Bearbeiten-Schaltfläche entfernt
        ];
    }

    public function getTitle(): string
    {
        return 'Firmeneinstellungen';
    }

    public function getSubheading(): ?string
    {
        return 'Verwalten Sie hier alle firmenspezifischen Einstellungen für Rechnungen und das System.';
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Zeige nur die erste (und einzige) Einstellung
        return parent::getTableQuery()->limit(1);
    }
}
