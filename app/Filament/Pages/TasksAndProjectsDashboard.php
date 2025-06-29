<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Attributes\Url;

class TasksAndProjectsDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.tasks-and-projects-dashboard';

    //protected static ?string $navigationGroup = 'Aufgaben';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Aufgaben & Projekttermine';

    protected static ?string $navigationLabel = 'Aufgaben & Projekttermine';

    #[Url]
    public ?string $timeFilter = 'today';

    public function mount(): void
    {
        // Stelle sicher, dass der timeFilter korrekt initialisiert wird
        $this->timeFilter = $this->timeFilter ?? 'today';
        $this->form->fill([
            'timeFilter' => $this->timeFilter
        ]);
    }

    public function booted(): void
    {
        // Dispatch initial event nach dem vollständigen Laden der Komponente
        $this->dispatch('timeFilterChanged', timeFilter: $this->timeFilter);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('timeFilter')
                    ->label('Zeitraum')
                    ->options([
                        'today' => 'Heute',
                        'next_7_days' => 'Nächste 7 Tage',
                        'next_30_days' => 'Nächste 30 Tage',
                    ])
                    ->default($this->timeFilter)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->timeFilter = $state;
                        $this->dispatch('timeFilterChanged', timeFilter: $state);
                    }),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            // \App\Filament\Widgets\FilteredTasksStatsWidget::class,
            // \App\Filament\Widgets\FilteredProjectMilestonesStatsWidget::class,
            \App\Filament\Widgets\FilteredTasksTableWidget::class,
            \App\Filament\Widgets\FilteredProjectMilestonesTableWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getTimeFilter(): string
    {
        return $this->timeFilter ?? 'today';
    }
}