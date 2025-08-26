<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('convert_to_customer')
                ->label('Zu Kunde konvertieren')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->action(function () {
                    $this->record->update(['customer_type' => 'business']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Lead konvertiert')
                        ->body('Lead wurde erfolgreich zu einem Geschäftskunden konvertiert.')
                        ->success()
                        ->send();
                        
                    return redirect(route('filament.admin.resources.customers.view', $this->record));
                })
                ->requiresConfirmation()
                ->modalHeading('Lead zu Kunde konvertieren')
                ->modalDescription('Möchten Sie diesen Lead zu einem Geschäftskunden konvertieren?')
                ->modalSubmitActionLabel('Konvertieren'),
        ];
    }
}
