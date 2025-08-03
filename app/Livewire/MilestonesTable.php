<?php

namespace App\Livewire;

use App\Models\ProjectMilestone;
use App\Models\ProjectAppointment;
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
use Illuminate\Support\Collection;

class MilestonesTable extends Component implements HasForms, HasTable
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
                // Kombiniere ProjectMilestone und ProjectAppointment über Union
                ProjectMilestone::query()
                    ->whereHas('project', function ($query) {
                        $query->where('solar_plant_id', $this->solarPlant->id);
                    })
                    ->with(['project', 'responsibleUser'])
                    ->select([
                        'id',
                        'project_id',
                        'title',
                        'description',
                        'type',
                        'planned_date as datetime',
                        'actual_date',
                        'status',
                        'responsible_user_id',
                        'completion_percentage',
                        'is_critical_path',
                        'created_at',
                        'updated_at'
                    ])
                    ->selectRaw("'milestone' as record_type")
                    ->union(
                        ProjectAppointment::query()
                            ->whereHas('project', function ($query) {
                                $query->where('solar_plant_id', $this->solarPlant->id);
                            })
                            ->with(['project', 'creator'])
                            ->select([
                                'id',
                                'project_id',
                                'title',
                                'description',
                                'type',
                                'start_datetime as datetime',
                                'end_datetime as actual_date',
                                'status',
                                'created_by as responsible_user_id',
                                \DB::raw('NULL as completion_percentage'),
                                \DB::raw('FALSE as is_critical_path'),
                                'created_at',
                                'updated_at'
                            ])
                            ->selectRaw("'appointment' as record_type")
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('record_type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'milestone' => 'Meilenstein',
                        'appointment' => 'Termin',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'milestone' => 'primary',
                        'appointment' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Kategorie')
                    ->formatStateUsing(function ($state, $record) {
                        // Add null check for record_type
                        if (!isset($record->record_type) || $record->record_type === null) {
                            return $state ?: 'Nicht angegeben';
                        }
                        
                        if ($record->record_type === 'milestone') {
                            return match($state) {
                                'planning' => 'Planung',
                                'approval' => 'Genehmigung',
                                'implementation' => 'Umsetzung',
                                'testing' => 'Testing',
                                'delivery' => 'Lieferung',
                                'payment' => 'Zahlung',
                                'review' => 'Review',
                                default => $state ?: 'Nicht angegeben',
                            };
                        } else {
                            return match($state) {
                                'meeting' => 'Meeting',
                                'deadline' => 'Deadline',
                                'review' => 'Review',
                                'milestone_check' => 'Meilenstein-Check',
                                'inspection' => 'Inspektion',
                                'training' => 'Schulung',
                                default => $state ?: 'Nicht angegeben',
                            };
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'planning', 'meeting' => 'info',
                        'approval', 'deadline' => 'warning',
                        'implementation', 'milestone_check' => 'primary',
                        'testing', 'inspection' => 'success',
                        'delivery', 'training' => 'secondary',
                        'payment' => 'gray',
                        'review' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state, $record) {
                        // Add null check for record_type
                        if (!isset($record->record_type) || $record->record_type === null) {
                            return $state ?: 'Unbekannt';
                        }
                        
                        if ($record->record_type === 'milestone') {
                            return match($state) {
                                'pending' => 'Ausstehend',
                                'in_progress' => 'In Bearbeitung',
                                'completed' => 'Abgeschlossen',
                                'delayed' => 'Verzögert',
                                'cancelled' => 'Abgebrochen',
                                default => $state ?: 'Unbekannt',
                            };
                        } else {
                            return match($state) {
                                'scheduled' => 'Geplant',
                                'confirmed' => 'Bestätigt',
                                'cancelled' => 'Abgesagt',
                                'completed' => 'Erledigt',
                                default => $state ?: 'Unbekannt',
                            };
                        }
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        // Add null check for record_type
                        if (!isset($record->record_type) || $record->record_type === null) {
                            return 'gray';
                        }
                        
                        if ($record->record_type === 'milestone') {
                            return match($state) {
                                'pending' => 'gray',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                'delayed' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            };
                        } else {
                            return match($state) {
                                'scheduled' => 'gray',
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default => 'gray',
                            };
                        }
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('datetime')
                    ->label('Datum/Zeit')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return 'Nicht festgelegt';
                        
                        $date = \Carbon\Carbon::parse($state);
                        // Add null check for record_type
                        if (isset($record->record_type) && $record->record_type === 'appointment') {
                            return $date->format('d.m.Y H:i');
                        } else {
                            return $date->format('d.m.Y');
                        }
                    })
                    ->color(function ($state, $record) {
                        if (!$state) return 'gray';
                        
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast() && $record->status !== 'completed') return 'danger';
                        if ($date->isToday()) return 'warning';
                        if ($date->isTomorrow()) return 'info';
                        return 'primary';
                    })
                    ->icon(function ($state, $record) {
                        if (!$state) return null;
                        
                        $date = \Carbon\Carbon::parse($state);
                        if ($date->isPast() && $record->status !== 'completed') return 'heroicon-o-exclamation-triangle';
                        if ($date->isToday()) return 'heroicon-o-clock';
                        // Add null check for record_type
                        if (isset($record->record_type) && $record->record_type === 'appointment') return 'heroicon-o-calendar-days';
                        return 'heroicon-o-calendar';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_date')
                    ->label('Tatsächliches Datum')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return 'Nicht erledigt';
                        
                        $date = \Carbon\Carbon::parse($state);
                        // Add null check for record_type
                        if (isset($record->record_type) && $record->record_type === 'appointment') {
                            return $date->format('d.m.Y H:i');
                        } else {
                            return $date->format('d.m.Y');
                        }
                    })
                    ->placeholder('Nicht erledigt')
                    ->color('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Fortschritt')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : 'N/A')
                    ->badge()
                    ->color(function ($state) {
                        if ($state === null) return 'gray';
                        if ($state >= 80) return 'success';
                        if ($state >= 50) return 'info';
                        if ($state >= 25) return 'warning';
                        return 'danger';
                    })
                    ->sortable()
                    ->alignCenter()
                    ->visible(fn ($record) => isset($record->record_type) && $record->record_type === 'milestone'),

                Tables\Columns\IconColumn::make('is_critical_path')
                    ->label('Kritischer Pfad')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->visible(fn ($record) => isset($record->record_type) && $record->record_type === 'milestone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projekt')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.projects.view', $record->project))
                    ->openUrlInNewTab(false)
                    ->limit(30)
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->placeholder('Keine Beschreibung')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('responsible_user.name')
                    ->label('Verantwortlich')
                    ->placeholder('Nicht zugewiesen')
                    ->color('info')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('time_status')
                    ->label('Zeitstatus')
                    ->state(function ($record) {
                        if (!$record->datetime) return 'Kein Datum';
                        
                        $date = \Carbon\Carbon::parse($record->datetime);
                        $now = now();
                        
                        if ($record->status === 'completed') return 'Abgeschlossen';
                        if ($date->isPast()) return 'Überfällig';
                        if ($date->isToday()) return 'Heute';
                        if ($date->isTomorrow()) return 'Morgen';
                        
                        $diffInDays = $now->diffInDays($date);
                        if ($diffInDays <= 7) return "In {$diffInDays} Tagen";
                        if ($diffInDays <= 30) return "In " . ceil($diffInDays / 7) . " Wochen";
                        
                        return "In " . ceil($diffInDays / 30) . " Monaten";
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->datetime) return 'gray';
                        
                        $date = \Carbon\Carbon::parse($record->datetime);
                        
                        if ($record->status === 'completed') return 'success';
                        if ($date->isPast()) return 'danger';
                        if ($date->isToday()) return 'warning';
                        if ($date->isTomorrow()) return 'info';
                        return 'primary';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('record_type')
                    ->label('Typ')
                    ->options([
                        'milestone' => 'Meilensteine',
                        'appointment' => 'Termine',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        // Milestone Stati
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                        // Appointment Stati
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Kategorie')
                    ->options([
                        // Milestone Typen
                        'planning' => 'Planung',
                        'approval' => 'Genehmigung',
                        'implementation' => 'Umsetzung',
                        'testing' => 'Testing',
                        'delivery' => 'Lieferung',
                        'payment' => 'Zahlung',
                        'review' => 'Review',
                        // Appointment Typen
                        'meeting' => 'Meeting',
                        'deadline' => 'Deadline',
                        'milestone_check' => 'Meilenstein-Check',
                        'inspection' => 'Inspektion',
                        'training' => 'Schulung',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->label('Zeitraum')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('datetime', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->where('datetime', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällige Termine')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('datetime', '<', now())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDate('datetime', today())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('datetime', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('next_week')
                    ->label('Nächste Woche')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('datetime', [
                            now()->addWeek()->startOfWeek(),
                            now()->addWeek()->endOfWeek()
                        ])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('critical_path')
                    ->label('Kritischer Pfad')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('is_critical_path', true)
                    )
                    ->toggle(),

                Tables\Filters\TernaryFilter::make('has_responsible')
                    ->label('Verantwortlichkeit')
                    ->placeholder('Alle Termine')
                    ->trueLabel('Nur zugewiesene')
                    ->falseLabel('Nur nicht zugewiesene')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('responsible_user_id'),
                        false: fn (Builder $query) => $query->whereNull('responsible_user_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(function ($record) {
                            if (isset($record->record_type) && $record->record_type === 'milestone') {
                                return route('filament.admin.resources.project-milestones.view', $record);
                            } else {
                                return route('filament.admin.resources.project-appointments.view', $record);
                            }
                        })
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(function ($record) {
                            if (isset($record->record_type) && $record->record_type === 'milestone') {
                                return route('filament.admin.resources.project-milestones.edit', $record);
                            } else {
                                return route('filament.admin.resources.project-appointments.edit', $record);
                            }
                        })
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('mark_completed')
                        ->label('Als erledigt markieren')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Termin abschließen')
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Termin als erledigt markieren möchten?')
                        ->modalSubmitActionLabel('Ja, abschließen')
                        ->form([
                            Forms\Components\DateTimePicker::make('actual_date')
                                ->label('Tatsächliches Datum/Zeit')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            if (isset($record->record_type) && $record->record_type === 'milestone') {
                                ProjectMilestone::find($record->id)->update([
                                    'status' => 'completed',
                                    'completion_percentage' => 100,
                                    'actual_date' => $data['actual_date'],
                                ]);
                            } else {
                                ProjectAppointment::find($record->id)->update([
                                    'status' => 'completed',
                                    'end_datetime' => $data['actual_date'],
                                ]);
                            }
                        }),

                    Tables\Actions\Action::make('postpone')
                        ->label('Verschieben')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->form([
                            Forms\Components\DateTimePicker::make('new_datetime')
                                ->label('Neues Datum/Zeit')
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Grund für Verschiebung')
                                ->placeholder('Optional: Grund angeben'),
                        ])
                        ->action(function ($record, array $data) {
                            if (isset($record->record_type) && $record->record_type === 'milestone') {
                                ProjectMilestone::find($record->id)->update([
                                    'planned_date' => $data['new_datetime'],
                                    'status' => 'delayed',
                                ]);
                            } else {
                                ProjectAppointment::find($record->id)->update([
                                    'start_datetime' => $data['new_datetime'],
                                ]);
                            }
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Absagen')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Termin absagen')
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Termin absagen möchten?')
                        ->modalSubmitActionLabel('Ja, absagen')
                        ->action(function ($record) {
                            if (isset($record->record_type) && $record->record_type === 'milestone') {
                                ProjectMilestone::find($record->id)->update(['status' => 'cancelled']);
                            } else {
                                ProjectAppointment::find($record->id)->update(['status' => 'cancelled']);
                            }
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
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Als erledigt markieren')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!in_array($record->status, ['completed', 'cancelled'])) {
                                    if (isset($record->record_type) && $record->record_type === 'milestone') {
                                        ProjectMilestone::find($record->id)->update([
                                            'status' => 'completed',
                                            'completion_percentage' => 100,
                                            'actual_date' => now(),
                                        ]);
                                    } else {
                                        ProjectAppointment::find($record->id)->update([
                                            'status' => 'completed',
                                            'end_datetime' => now(),
                                        ]);
                                    }
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Absagen')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Termine absagen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Termine absagen möchten?')
                        ->modalSubmitActionLabel('Ja, absagen')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if (!in_array($record->status, ['completed', 'cancelled'])) {
                                    if (isset($record->record_type) && $record->record_type === 'milestone') {
                                        ProjectMilestone::find($record->id)->update(['status' => 'cancelled']);
                                    } else {
                                        ProjectAppointment::find($record->id)->update(['status' => 'cancelled']);
                                    }
                                }
                            }
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Termine löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Termine löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen'),
                ]),
            ])
            ->defaultSort('datetime', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Termine zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Termine oder Meilensteine zu den Projekten dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'milestones-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.milestones-table');
    }
}
