<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SolarPlant;
use App\Models\CompanySetting;
use App\Services\MermaidChartService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class MermaidChartGenerator extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $selectedSolarPlantId = null;
    public string $generatedChart = '';
    public string $currentTemplate = '';

    public function mount(): void
    {
        $companySetting = CompanySetting::current();
        $this->currentTemplate = $companySetting->mermaid_chart_template ?? '';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedSolarPlantId')
                    ->label('Solaranlage auswählen')
                    ->options(SolarPlant::all()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Wählen Sie eine Solaranlage aus...')
                    ->required(),
                
                Textarea::make('currentTemplate')
                    ->label('Mermaid Template')
                    ->rows(10)
                    ->placeholder('Hier wird das aktuelle Template angezeigt...')
                    ->disabled(),
                
                Textarea::make('generatedChart')
                    ->label('Generierter Mermaid-Code')
                    ->rows(15)
                    ->placeholder('Hier wird der generierte Mermaid-Code angezeigt...')
                    ->extraAttributes(['readonly' => true]),
            ])
            ->statePath('data');
    }

    public function generateChart(): void
    {
        if (!$this->selectedSolarPlantId) {
            Notification::make()
                ->title('Fehler')
                ->body('Bitte wählen Sie eine Solaranlage aus.')
                ->danger()
                ->send();
            return;
        }

        try {
            $solarPlant = SolarPlant::findOrFail($this->selectedSolarPlantId);
            $mermaidService = new MermaidChartService();
            
            $this->generatedChart = $mermaidService->generateSolarPlantChart($solarPlant);
            
            Notification::make()
                ->title('Erfolg')
                ->body('Mermaid-Chart wurde erfolgreich generiert!')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body('Fehler beim Generieren des Charts: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function copyToClipboard(): void
    {
        if (empty($this->generatedChart)) {
            Notification::make()
                ->title('Fehler')
                ->body('Kein Chart-Code zum Kopieren vorhanden.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('copy-to-clipboard', $this->generatedChart);
        
        Notification::make()
            ->title('Kopiert')
            ->body('Mermaid-Code wurde in die Zwischenablage kopiert!')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.mermaid-chart-generator');
    }
}