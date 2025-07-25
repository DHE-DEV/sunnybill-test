<?php

namespace App\Filament\Resources\SolarPlantBillingResource\Pages;

use App\Filament\Resources\SolarPlantBillingResource;
use App\Services\SolarPlantBillingPdfService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewSolarPlantBilling extends ViewRecord
{
    protected static string $resource = SolarPlantBillingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generatePdf')
                ->label('PDF Abrechnung generieren')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    try {
                        $pdfService = new SolarPlantBillingPdfService();
                        
                        return $pdfService->downloadBillingPdf($this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim PDF-Export')
                            ->body('Die PDF-Abrechnung konnte nicht erstellt werden: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        
                        return null;
                    }
                }),
            Actions\EditAction::make(),
        ];
    }
}
