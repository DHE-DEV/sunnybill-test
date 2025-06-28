<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Termine';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->milestones()->count();
    }

    protected static ?string $modelLabel = 'Projekttermin';

    protected static ?string $pluralModelLabel = 'Projekttermine';

    protected static ?string $icon = 'heroicon-o-calendar-days';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Projekttermin')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Baugenehmigung erhalten')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Detaillierte Beschreibung des Termins...')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('planned_date')
                                    ->label('Geplantes Datum')
                                    ->required()
                                    ->displayFormat('d.m.Y')
                                    ->default(now()),
                                Forms\Components\DatePicker::make('actual_date')
                                    ->label('Tatsächliches Datum')
                                    ->displayFormat('d.m.Y')
                                    ->after('planned_date'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'planned' => 'Geplant',
                                        'in_progress' => 'In Bearbeitung',
                                        'completed' => 'Abgeschlossen',
                                        'delayed' => 'Verzögert',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->default('planned')
                                    ->required(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Geplant')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->is_overdue ? 'danger' : ($record->is_today ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('actual_date')
                    ->label('Tatsächlich')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_variance')
                    ->label('Abweichung')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null) return '—';
                        if ($state === 0) return 'Pünktlich';
                        return $state > 0 ? "+{$state} Tage" : "{$state} Tage";
                    })
                    ->color(fn ($state) => $state === null ? 'gray' : ($state === 0 ? 'success' : ($state > 0 ? 'danger' : 'success')))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'planned' => 'Geplant',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'planned' => 'gray',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'delayed' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planned' => 'Geplant',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_date', '<', now()->toDateString())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    ),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Anstehend (nächste 7 Tage)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('planned_date', [now()->toDateString(), now()->addWeek()->toDateString()])
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    ),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Projekttermin hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('3xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Sort order setzen
                        $maxSortOrder = \App\Models\SolarPlantMilestone::where('solar_plant_id', $this->getOwnerRecord()->id)
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalWidth('3xl'),
                    Tables\Actions\EditAction::make()
                        ->modalWidth('3xl'),
                    Tables\Actions\Action::make('mark_completed')
                        ->label('Als abgeschlossen markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'completed',
                                'actual_date' => now()->toDateString(),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Termin als abgeschlossen markiert')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-o-cog-6-tooth')
                ->tooltip('Aktionen')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_completed')
                        ->label('Als abgeschlossen markieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'completed',
                                    'actual_date' => now()->toDateString(),
                                ]);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Termine als abgeschlossen markiert')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('Keine Projekttermine vorhanden')
            ->emptyStateDescription('Erstellen Sie den ersten Projekttermin für diese Solaranlage.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}