<?php

namespace App\Livewire;

use App\Models\Project;
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
use Filament\Notifications\Notification;
use App\Models\User;

class ProjectsTable extends Component implements HasForms, HasTable
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
                Project::query()
                    ->where('solar_plant_id', $this->solarPlant->id)
                    ->with(['projectManager', 'customer', 'supplier', 'creator', 'milestones', 'appointments'])
            )
            ->headerActions([
                Tables\Actions\Action::make('add_project')
                    ->label('Projekt hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Projektname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Installation Monitoring-System'),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Detaillierte Projektbeschreibung')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Projekttyp')
                                    ->options([
                                        'installation' => 'Installation',
                                        'maintenance' => 'Wartung',
                                        'upgrade' => 'Upgrade',
                                        'repair' => 'Reparatur',
                                        'inspection' => 'Inspektion',
                                        'documentation' => 'Dokumentation',
                                        'planning' => 'Planung',
                                        'consulting' => 'Beratung',
                                    ])
                                    ->default('installation')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'planning' => 'Planung',
                                        'active' => 'Aktiv',
                                        'on_hold' => 'Pausiert',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->default('planning')
                                    ->required(),
                                Forms\Components\Select::make('priority')
                                    ->label('Priorität')
                                    ->options([
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend',
                                    ])
                                    ->default('medium')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Startdatum')
                                    ->default(now())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d.m.Y'),
                                Forms\Components\DatePicker::make('planned_end_date')
                                    ->label('Geplantes Ende')
                                    ->after('start_date')
                                    ->native(false)
                                    ->displayFormat('d.m.Y'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('budget')
                                    ->label('Budget')
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->placeholder('z.B. 10000.00')
                                    ->helperText('Geplantes Budget für das Projekt'),
                                Forms\Components\TextInput::make('progress_percentage')
                                    ->label('Fortschritt (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%')
                                    ->helperText('Aktueller Projektfortschritt'),
                            ]),
                        Forms\Components\Select::make('project_manager_id')
                            ->label('Projektleiter')
                            ->options(User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Projektleiter auswählen')
                            ->default(auth()->id()),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Tags hinzufügen...')
                            ->suggestions([
                                'wartung',
                                'installation',
                                'upgrade',
                                'dringend',
                                'monitoring',
                                'solar',
                                'batterie',
                            ])
                            ->helperText('Fügen Sie Tags zur besseren Organisation hinzu'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Projekt aktiv')
                            ->default(true)
                            ->helperText('Aktive Projekte werden in Übersichten angezeigt'),
                    ])
                    ->action(function (array $data) {
                        // Erstelle das neue Projekt
                        $project = new Project();
                        $project->name = $data['name'];
                        $project->description = $data['description'] ?? null;
                        $project->type = $data['type'];
                        $project->status = $data['status'];
                        $project->priority = $data['priority'];
                        $project->start_date = $data['start_date'];
                        $project->planned_end_date = $data['planned_end_date'] ?? null;
                        $project->budget = $data['budget'] ?? null;
                        $project->progress_percentage = $data['progress_percentage'] ?? 0;
                        $project->project_manager_id = $data['project_manager_id'] ?? null;
                        $project->tags = $data['tags'] ?? [];
                        $project->is_active = $data['is_active'] ?? true;
                        $project->solar_plant_id = $this->solarPlant->id;
                        $project->created_by = auth()->id();
                        
                        $project->save();
                        
                        Notification::make()
                            ->title('Projekt erstellt')
                            ->body("Das Projekt '{$project->name}' wurde erfolgreich erstellt.")
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Neues Projekt erstellen')
                    ->modalDescription('Erstellen Sie ein neues Projekt für diese Solaranlage.')
                    ->modalSubmitActionLabel('Projekt erstellen')
                    ->modalWidth('lg'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('project_number')
                    ->label('Projekt-Nr.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->url(fn ($record) => route('filament.admin.resources.projects.view', $record))
                    ->openUrlInNewTab(false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Projektname')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.projects.view', $record))
                    ->openUrlInNewTab(false)
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'upgrade' => 'Upgrade',
                        'repair' => 'Reparatur',
                        'inspection' => 'Inspektion',
                        'documentation' => 'Dokumentation',
                        'planning' => 'Planung',
                        'consulting' => 'Beratung',
                        default => $state ?: 'Nicht angegeben',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'installation' => 'primary',
                        'maintenance' => 'warning',
                        'upgrade' => 'success',
                        'repair' => 'danger',
                        'inspection' => 'info',
                        'documentation' => 'gray',
                        'planning' => 'info',
                        'consulting' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                        default => $state ?: 'Unbekannt',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'planning' => 'gray',
                        'active' => 'success',
                        'on_hold' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        default => $state ?: 'Nicht angegeben',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Fortschritt')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : 'Nicht angegeben')
                    ->badge()
                    ->color(function ($state) {
                        if ($state === null) return 'gray';
                        if ($state >= 80) return 'success';
                        if ($state >= 50) return 'info';
                        if ($state >= 25) return 'warning';
                        return 'danger';
                    })
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('projectManager.name')
                    ->label('Projektleiter')
                    ->placeholder('Nicht zugewiesen')
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Startdatum')
                    ->date('d.m.Y')
                    ->placeholder('Nicht festgelegt')
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_end_date')
                    ->label('Geplantes Ende')
                    ->date('d.m.Y')
                    ->placeholder('Nicht festgelegt')
                    ->color(function ($record) {
                        if (!$record->planned_end_date) return 'gray';
                        if ($record->is_overdue) return 'danger';
                        if ($record->days_remaining !== null && $record->days_remaining <= 7) return 'warning';
                        return 'success';
                    })
                    ->icon(function ($record) {
                        if (!$record->planned_end_date) return null;
                        if ($record->is_overdue) return 'heroicon-o-exclamation-triangle';
                        if ($record->days_remaining !== null && $record->days_remaining <= 7) return 'heroicon-o-clock';
                        return 'heroicon-o-calendar';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_end_date')
                    ->label('Tatsächliches Ende')
                    ->date('d.m.Y')
                    ->placeholder('Noch nicht beendet')
                    ->color('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('budget')
                    ->label('Budget')
                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 2, ',', '.') : 'Nicht angegeben')
                    ->color('primary')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('actual_costs')
                    ->label('Tatsächliche Kosten')
                    ->formatStateUsing(fn ($state) => $state ? '€ ' . number_format($state, 2, ',', '.') : 'Nicht erfasst')
                    ->color(function ($record) {
                        if (!$record->actual_costs || !$record->budget) return 'gray';
                        return $record->actual_costs > $record->budget ? 'danger' : 'success';
                    })
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cost_variance')
                    ->label('Kostenabweichung')
                    ->state(function ($record) {
                        if (!$record->actual_costs || !$record->budget) return 'Nicht verfügbar';
                        $variance = $record->actual_costs - $record->budget;
                        $percentage = round(($variance / $record->budget) * 100, 1);
                        return ($variance >= 0 ? '+' : '') . '€ ' . number_format($variance, 2, ',', '.') . ' (' . ($percentage >= 0 ? '+' : '') . $percentage . '%)';
                    })
                    ->color(function ($record) {
                        if (!$record->actual_costs || !$record->budget) return 'gray';
                        return $record->actual_costs > $record->budget ? 'danger' : 'success';
                    })
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('milestones_count')
                    ->label('Meilensteine')
                    ->counts('milestones')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('open_milestones_count')
                    ->label('Offene Meilensteine')
                    ->state(fn ($record) => $record->milestones()->whereIn('status', ['pending', 'in_progress'])->count())
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Termine')
                    ->counts('appointments')
                    ->badge()
                    ->color('secondary')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state)) return 'Keine Tags';
                        return implode(', ', $state);
                    })
                    ->placeholder('Keine Tags')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Projekttyp')
                    ->options([
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'upgrade' => 'Upgrade',
                        'repair' => 'Reparatur',
                        'inspection' => 'Inspektion',
                        'documentation' => 'Dokumentation',
                        'planning' => 'Planung',
                        'consulting' => 'Beratung',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ]),

                Tables\Filters\SelectFilter::make('project_manager_id')
                    ->label('Projektleiter')
                    ->relationship('projectManager', 'name')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle Projekte')
                    ->trueLabel('Nur aktive Projekte')
                    ->falseLabel('Nur inaktive Projekte'),

                Tables\Filters\Filter::make('date_range')
                    ->label('Zeitraum')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start ab'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start bis'),
                        Forms\Components\DatePicker::make('end_from')
                            ->label('Ende ab'),
                        Forms\Components\DatePicker::make('end_until')
                            ->label('Ende bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '<=', $date),
                            )
                            ->when(
                                $data['end_from'],
                                fn (Builder $query, $date): Builder => $query->where('planned_end_date', '>=', $date),
                            )
                            ->when(
                                $data['end_until'],
                                fn (Builder $query, $date): Builder => $query->where('planned_end_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällige Projekte')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_end_date', '<', now()->toDateString())
                              ->where('status', '!=', 'completed')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('ending_soon')
                    ->label('Enden bald (7 Tage)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_end_date', '>=', now())
                              ->where('planned_end_date', '<=', now()->addDays(7))
                              ->where('status', '!=', 'completed')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('high_priority')
                    ->label('Hohe Priorität')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereIn('priority', ['high', 'urgent'])
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('budget_exceeded')
                    ->label('Budget überschritten')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('actual_costs > budget')
                              ->whereNotNull('actual_costs')
                              ->whereNotNull('budget')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('progress_filter')
                    ->label('Fortschritt')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('progress_min')
                                    ->label('Fortschritt min (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('progress_max')
                                    ->label('Fortschritt max (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('100'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['progress_min'],
                                fn (Builder $query, $value): Builder => $query->where('progress_percentage', '>=', $value),
                            )
                            ->when(
                                $data['progress_max'],
                                fn (Builder $query, $value): Builder => $query->where('progress_percentage', '<=', $value),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.projects.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.projects.edit', $record))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('mark_active')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'active')
                        ->action(function ($record) {
                            $record->update(['status' => 'active']);
                        }),

                    Tables\Actions\Action::make('mark_completed')
                        ->label('Abschließen')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation()
                        ->modalHeading('Projekt abschließen')
                        ->modalDescription('Sind Sie sicher, dass Sie dieses Projekt als abgeschlossen markieren möchten?')
                        ->modalSubmitActionLabel('Ja, abschließen')
                        ->form([
                            Forms\Components\DatePicker::make('actual_end_date')
                                ->label('Tatsächliches Enddatum')
                                ->default(now())
                                ->required(),
                            Forms\Components\TextInput::make('actual_costs')
                                ->label('Tatsächliche Kosten')
                                ->numeric()
                                ->prefix('€')
                                ->placeholder('Optional'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'completed',
                                'progress_percentage' => 100,
                                'actual_end_date' => $data['actual_end_date'],
                                'actual_costs' => $data['actual_costs'] ?? $record->actual_costs,
                            ]);
                        }),

                    Tables\Actions\Action::make('put_on_hold')
                        ->label('Pausieren')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->action(function ($record) {
                            $record->update(['status' => 'on_hold']);
                        }),

                    Tables\Actions\Action::make('assign_to_me')
                        ->label('Mir zuweisen')
                        ->icon('heroicon-o-user')
                        ->color('primary')
                        ->visible(fn ($record) => $record->project_manager_id !== auth()->id())
                        ->action(function ($record) {
                            $record->update(['project_manager_id' => auth()->id()]);
                        }),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->form([
                            Forms\Components\TextInput::make('name')
                                ->label('Neuer Projektname')
                                ->required()
                                ->default(fn ($record) => $record->name . ' (Kopie)'),
                        ])
                        ->action(function ($record, array $data) {
                            $newProject = $record->replicate([
                                'project_number',
                                'created_at',
                                'updated_at'
                            ]);
                            $newProject->name = $data['name'];
                            $newProject->status = 'planning';
                            $newProject->progress_percentage = 0;
                            $newProject->actual_end_date = null;
                            $newProject->actual_costs = null;
                            $newProject->save();
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
                    Tables\Actions\BulkAction::make('mark_active')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status !== 'active') {
                                    $record->update(['status' => 'active']);
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('put_on_hold')
                        ->label('Pausieren')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->update(['status' => 'on_hold']);
                                }
                            }
                        }),

                    Tables\Actions\BulkAction::make('assign_manager')
                        ->label('Projektleiter zuweisen')
                        ->icon('heroicon-o-user')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('project_manager_id')
                                ->label('Projektleiter')
                                ->relationship('projectManager', 'name')
                                ->required()
                                ->preload()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['project_manager_id' => $data['project_manager_id']]);
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
                        ->modalHeading('Projekte löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Projekte löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
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
            ->emptyStateHeading('Keine Projekte zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Projekte zu dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'projects-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.projects-table');
    }
}
