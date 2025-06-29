<?php

namespace App\Filament\Widgets;

use App\Models\SolarPlantMilestone;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class FilteredProjectMilestonesTableWidget extends BaseWidget
{
    public ?string $timeFilter = 'today';
    
    protected static ?string $heading = 'Projekttermine';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';

    protected $listeners = ['timeFilterChanged'];

    public function mount(?string $timeFilter = null): void
    {
        $this->timeFilter = $timeFilter ?? 'today';
    }

    public function booted(): void
    {
        // Stelle sicher, dass der Filter beim ersten Laden angewendet wird
        if (!$this->timeFilter) {
            $this->timeFilter = 'today';
        }
    }

    public function timeFilterChanged($timeFilter): void
    {
        $this->timeFilter = $timeFilter;
        // Trigger table refresh
        $this->resetTable();
        // Force re-render
        $this->dispatch('$refresh');
    }

    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        
        return $table
            ->query(
                SolarPlantMilestone::query()
                    ->whereBetween('planned_date', $dateRange)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->with(['solarPlant', 'projectManager', 'lastResponsibleUser'])
                    ->orderByRaw("
                        CASE 
                            WHEN status = 'delayed' THEN 1
                            WHEN planned_date < CURDATE() THEN 2
                            WHEN planned_date = CURDATE() THEN 3
                            WHEN status = 'in_progress' THEN 4
                            ELSE 5
                        END
                    ")
                    ->orderBy('planned_date', 'asc')
            )
            ->columns([
                TextColumn::make('solarPlant.name')
                    ->label('Projekt')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->weight('bold')
                    ->tooltip(fn (SolarPlantMilestone $record): string => $record->solarPlant->name ?? 'Unbekanntes Projekt')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('title')
                    ->label('Meilenstein')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('medium')
                    ->tooltip(fn (SolarPlantMilestone $record): string => $record->title)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(40)
                    ->placeholder('Keine Beschreibung')
                    ->tooltip(fn (SolarPlantMilestone $record): ?string => $record->description)
                    ->toggleable(),

                TextColumn::make('planned_date')
                    ->label('Geplant')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (SolarPlantMilestone $record): string =>
                        $record->is_overdue ? 'danger' :
                        ($record->is_today ? 'warning' : 'primary')
                    )
                    ->icon(fn (SolarPlantMilestone $record): string =>
                        $record->is_overdue ? 'heroicon-o-exclamation-triangle' :
                        ($record->is_today ? 'heroicon-o-clock' : '')
                    )
                    ->width('100px')
                    ->toggleable(isToggledHiddenByDefault: false),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'planned',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'warning' => 'delayed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Geplant',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    })
                    ->sortable()
                    ->width('120px')
                    ->toggleable(),

                TextColumn::make('actual_date')
                    ->label('Tatsächlich')
                    ->date('d.m.Y')
                    ->placeholder('Noch nicht erreicht')
                    ->color('success')
                    ->sortable()
                    ->width('100px')
                    ->toggleable(),

                TextColumn::make('projectManager.name')
                    ->label('Inhaber')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->placeholder('Nicht zugewiesen')
                    ->tooltip(fn (SolarPlantMilestone $record): ?string => $record->projectManager?->name)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('lastResponsibleUser.name')
                    ->label('Zuständig')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->placeholder('Nicht zugewiesen')
                    ->tooltip(fn (SolarPlantMilestone $record): ?string => $record->lastResponsibleUser?->name)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('solarPlant.location')
                    ->label('Standort')
                    ->limit(20)
                    ->placeholder('Kein Standort')
                    ->tooltip(fn (SolarPlantMilestone $record): ?string => $record->solarPlant->location ?? null)
                    ->toggleable(),

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->url(fn (SolarPlantMilestone $record): string =>
                            route('filament.admin.resources.solar-plants.view', $record->solarPlant)
                        )
                        ->openUrlInNewTab(false),
                        
                    Tables\Actions\Action::make('complete')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (SolarPlantMilestone $record) {
                            $record->update([
                                'status' => 'completed',
                                'actual_date' => now()->toDateString(),
                            ]);
                        })
                        ->visible(fn (SolarPlantMilestone $record): bool =>
                            !in_array($record->status, ['completed', 'cancelled'])
                        )
                        ->requiresConfirmation(),
                        
                    Tables\Actions\Action::make('delay')
                        ->label('Verzögern')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(function (SolarPlantMilestone $record) {
                            $record->update(['status' => 'delayed']);
                        })
                        ->visible(fn (SolarPlantMilestone $record): bool =>
                            $record->status !== 'delayed' && !in_array($record->status, ['completed', 'cancelled'])
                        ),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->headerActions([
                Tables\Actions\Action::make('toggleColumns')
                    ->label('Spalten')
                    ->icon('heroicon-o-view-columns')
                    ->color('gray')
                    ->button()
                    ->action(fn () => null)
                    ->extraAttributes(['x-data' => '{}', 'x-on:click' => '$dispatch("toggle-table-columns")']),
            ])
            ->emptyStateHeading('Keine Projekttermine gefunden')
            ->emptyStateDescription('Für den gewählten Zeitraum gibt es keine offenen Projekttermine.')
            ->emptyStateIcon('heroicon-o-calendar')
            ->heading('Projekttermine (' . $this->getPeriodLabel() . ')');
    }
    
    protected function getDateRange(): array
    {
        // Startdatum ist immer weit in der Vergangenheit, um überfällige Projekttermine einzuschließen
        $startDate = Carbon::today()->subYears(10)->toDateString();
        
        return match($this->timeFilter) {
            'today' => [
                $startDate,
                Carbon::today()->toDateString()
            ],
            'next_7_days' => [
                $startDate,
                Carbon::today()->copy()->addDays(7)->toDateString()
            ],
            'next_30_days' => [
                $startDate,
                Carbon::today()->copy()->addDays(30)->toDateString()
            ],
            default => [
                $startDate,
                Carbon::today()->toDateString()
            ]
        };
    }
    
    protected function getPeriodLabel(): string
    {
        return match($this->timeFilter) {
            'today' => 'Heute',
            'next_7_days' => 'Nächste 7 Tage',
            'next_30_days' => 'Nächste 30 Tage',
            default => 'Heute'
        };
    }
}