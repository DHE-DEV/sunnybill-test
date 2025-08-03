<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class TasksTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public SolarPlant $solarPlant;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->where(function ($query) {
                        $query->where('solar_plant_id', $this->solarPlant->id)
                              ->orWhere('applies_to_all_solar_plants', true);
                    })
                    ->with(['taskType', 'assignedTo', 'customer', 'supplier', 'creator'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('task_number')
                    ->label('Aufgaben-Nr.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->url(fn ($record) => route('filament.admin.resources.tasks.view', $record))
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.tasks.view', $record))
                    ->openUrlInNewTab(false)
                    ->limit(50),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        'blocker' => 'Blockierend',
                        default => $state ?: 'Nicht angegeben',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        'blocker' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warten auf extern',
                        'waiting_internal' => 'Warten auf intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                        default => $state ?: 'Unbekannt',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'open' => 'gray',
                        'in_progress' => 'info',
                        'waiting_external' => 'warning',
                        'waiting_internal' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('taskType.name')
                    ->label('Typ')
                    ->placeholder('Kein Typ')
                    ->badge()
                    ->color('secondary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Zugewiesen an')
                    ->placeholder('Nicht zugewiesen')
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Fälligkeitsdatum')
                    ->date('d.m.Y')
                    ->placeholder('Kein Datum')
                    ->color(function ($record) {
                        if (!$record->due_date) return 'gray';
                        if ($record->is_overdue) return 'danger';
                        if ($record->is_due_today) return 'warning';
                        return 'success';
                    })
                    ->icon(function ($record) {
                        if (!$record->due_date) return null;
                        if ($record->is_overdue) return 'heroicon-o-exclamation-triangle';
                        if ($record->is_due_today) return 'heroicon-o-clock';
                        return 'heroicon-o-calendar';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_minutes')
                    ->label('Geschätzte Zeit')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Nicht geschätzt';
                        
                        $hours = floor($state / 60);
                        $minutes = $state % 60;
                        
                        if ($hours > 0) {
                            return $minutes > 0 ? "{$hours}h {$minutes}min" : "{$hours}h";
                        }
                        return "{$minutes}min";
                    })
                    ->color('gray')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('actual_minutes')
                    ->label('Tatsächliche Zeit')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Nicht erfasst';
                        
                        $hours = floor($state / 60);
                        $minutes = $state % 60;
                        
                        if ($hours > 0) {
                            return $minutes > 0 ? "{$hours}h {$minutes}min" : "{$hours}h";
                        }
                        return "{$minutes}min";
                    })
                    ->color('info')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('applies_to_all_solar_plants')
                    ->label('Alle Anlagen')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-building-office-2')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(function ($record) {
                        return $record->applies_to_all_solar_plants 
                            ? 'Gilt für alle Solaranlagen'
                            : 'Nur für diese Solaranlage';
                    }),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->placeholder('Kein Kunde')
                    ->color('primary')
                    ->url(fn ($record) => $record->customer ? route('filament.admin.resources.customers.view', $record->customer) : null)
                    ->openUrlInNewTab(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Lieferant')
                    ->placeholder('Kein Lieferant')
                    ->color('warning')
                    ->url(fn ($record) => $record->supplier ? route('filament.admin.resources.suppliers.view', $record->supplier) : null)
                    ->openUrlInNewTab(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('labels')
                    ->label('Labels')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state)) return 'Keine Labels';
                        return implode(', ', $state);
                    })
                    ->placeholder('Keine Labels')
                    ->color('secondary')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->placeholder('Unbekannt')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Abgeschlossen am')
                    ->date('d.m.Y H:i')
                    ->placeholder('Nicht abgeschlossen')
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Offen',
                        'in_progress' => 'In Bearbeitung',
                        'waiting_external' => 'Warten auf extern',
                        'waiting_internal' => 'Warten auf intern',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        'blocker' => 'Blockierend',
                    ]),

                Tables\Filters\SelectFilter::make('task_type_id')
                    ->label('Aufgabentyp')
                    ->relationship('taskType', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Zugewiesen an')
                    ->relationship('assignedTo', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('applies_to_all_solar_plants')
                    ->label('Gültigkeit')
                    ->placeholder('Alle Aufgaben')
                    ->trueLabel('Nur globale Aufgaben')
                    ->falseLabel('Nur spezifische Aufgaben'),

                Tables\Filters\Filter::make('due_date')
                    ->label('Fälligkeitsdatum')
                    ->form([
                        Forms\Components\DatePicker::make('due_from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('due_until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->where('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->where('due_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällige Aufgaben')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', '<', now()->toDateString())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('due_today')
                    ->label('Heute fällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('due_date', now()->toDateString())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('high_priority')
                    ->label('Hohe Priorität')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereIn('priority', ['high', 'urgent', 'blocker'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('has_customer')
                    ->label('Mit Kundenbezug')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('customer_id')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('has_supplier')
                    ->label('Mit Lieferantenbezug')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('supplier_id')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.tasks.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.tasks.edit', $record))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('mark_in_progress')
                        ->label('In Bearbeitung')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'open')
                        ->action(function ($record) {
                            $record->markAsInProgress();
                        }),

                    Tables\Actions\Action::make('mark_completed')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Aufgabe abschließen')
                        ->modalDescription('Sind Sie sicher, dass Sie diese Aufgabe als abgeschlossen markieren möchten?')
                        ->modalSubmitActionLabel('Ja, abschließen')
                        ->action(function ($record) {
                            $record->markAsCompleted();
                        }),

                    Tables\Actions\Action::make('assign_to_me')
                        ->label('Mir zuweisen')
                        ->icon('heroicon-o-user')
                        ->color('primary')
                        ->visible(fn ($record) => $record->assigned_to !== auth()->id())
                        ->action(function ($record) {
                            $record->update(['assigned_to' => auth()->id()]);
                        }),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function ($record) {
                            $record->duplicate();
                        }),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_in_progress')
                        ->label('In Bearbeitung setzen')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'open') {
                                    $record->markAsInProgress();
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aufgaben abschließen')
                        ->modalDescription('Sind Sie sicher, dass Sie alle ausgewählten Aufgaben als abgeschlossen markieren möchten?')
                        ->modalSubmitActionLabel('Ja, alle abschließen')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!in_array($record->status, ['completed', 'cancelled'])) {
                                    $record->markAsCompleted();
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('assign_to_me')
                        ->label('Mir zuweisen')
                        ->icon('heroicon-o-user')
                        ->color('primary')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['assigned_to' => auth()->id()]);
                            }
                        }),

                    Tables\Actions\BulkAction::make('set_priority')
                        ->label('Priorität setzen')
                        ->icon('heroicon-o-flag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('priority')
                                ->label('Neue Priorität')
                                ->options([
                                    'low' => 'Niedrig',
                                    'medium' => 'Mittel',
                                    'high' => 'Hoch',
                                    'urgent' => 'Dringend',
                                    'blocker' => 'Blockierend',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['priority' => $data['priority']]);
                            }
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Aufgaben löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Aufgaben löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Aufgaben zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Aufgaben zu dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'tasks-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.tasks-table');
    }
}
