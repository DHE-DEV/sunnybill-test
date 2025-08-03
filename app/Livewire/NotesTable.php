<?php

namespace App\Livewire;

use App\Models\SolarPlantNote;
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

class NotesTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public SolarPlant $solarPlant;
    public bool $showOnlyFavorites = false;

    public function mount(SolarPlant $solarPlant, bool $showOnlyFavorites = false): void
    {
        $this->solarPlant = $solarPlant;
        $this->showOnlyFavorites = $showOnlyFavorites;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SolarPlantNote::query()
                    ->where('solar_plant_id', $this->solarPlant->id)
                    ->when($this->showOnlyFavorites, fn (Builder $query) => $query->where('is_favorite', true))
                    ->with(['user', 'solarPlant'])
            )
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('⭐')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->action(
                        Tables\Actions\Action::make('toggleFavorite')
                            ->action(function (SolarPlantNote $record) {
                                $record->update(['is_favorite' => !$record->is_favorite]);
                                
                                Notification::make()
                                    ->title($record->is_favorite ? 'Als Favorit markiert' : 'Favorit entfernt')
                                    ->success()
                                    ->send();
                            })
                    )
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->limit(40)
                    ->tooltip(fn (SolarPlantNote $record): string => $record->title),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'general' => 'Allgemein',
                        'maintenance' => 'Wartung',
                        'issue' => 'Problem',
                        'improvement' => 'Verbesserung',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'general' => 'primary',
                        'maintenance' => 'warning',
                        'issue' => 'danger',
                        'improvement' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Inhalt')
                    ->limit(80)
                    ->tooltip(fn (SolarPlantNote $record): string => $record->content)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Erstellt von')
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'maintenance' => 'Wartung',
                        'issue' => 'Problem',
                        'improvement' => 'Verbesserung',
                    ]),

                Tables\Filters\Filter::make('favorites_only')
                    ->label('Nur Favoriten')
                    ->query(fn (Builder $query): Builder => $query->where('is_favorite', true))
                    ->toggle(),

                Tables\Filters\Filter::make('created_today')
                    ->label('Heute erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('created_this_week')
                    ->label('Diese Woche erstellt')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('created_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Erstellt von')
                    ->relationship('user', 'name')
                    ->preload()
                    ->multiple(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Notiz')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Kurzer Titel für die Notiz'),

                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'general' => 'Allgemein',
                                'maintenance' => 'Wartung',
                                'issue' => 'Problem',
                                'improvement' => 'Verbesserung',
                            ])
                            ->default('general')
                            ->required(),

                        Forms\Components\Textarea::make('content')
                            ->label('Inhalt')
                            ->required()
                            ->rows(4)
                            ->placeholder('Beschreibung der Notiz...'),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Als Favorit markieren')
                            ->default(false)
                            ->helperText('Favoriten werden in der Übersicht hervorgehoben'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sortierreihenfolge')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Zahlen werden zuerst angezeigt'),
                    ])
                    ->action(function (array $data) {
                        $data['solar_plant_id'] = $this->solarPlant->id;
                        $data['user_id'] = auth()->id();
                        
                        SolarPlantNote::create($data);
                        
                        Notification::make()
                            ->title('Notiz erstellt')
                            ->body('Die Notiz wurde erfolgreich zur Solaranlage hinzugefügt.')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Neue Notiz hinzufügen')
                    ->modalSubmitActionLabel('Notiz erstellen')
                    ->modalWidth('lg'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('Titel')
                                ->disabled(),

                            Forms\Components\Select::make('type')
                                ->label('Typ')
                                ->options([
                                    'general' => 'Allgemein',
                                    'maintenance' => 'Wartung',
                                    'issue' => 'Problem',
                                    'improvement' => 'Verbesserung',
                                ])
                                ->disabled(),

                            Forms\Components\Textarea::make('content')
                                ->label('Inhalt')
                                ->rows(6)
                                ->disabled(),

                            Forms\Components\Toggle::make('is_favorite')
                                ->label('Favorit')
                                ->disabled(),

                            Forms\Components\Placeholder::make('created_info')
                                ->label('Erstellt')
                                ->content(fn (SolarPlantNote $record): string => 
                                    "Von {$record->user->name} am {$record->formatted_created_at}"
                                ),
                        ])
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Schließen'),

                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('Titel')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('type')
                                ->label('Typ')
                                ->options([
                                    'general' => 'Allgemein',
                                    'maintenance' => 'Wartung',
                                    'issue' => 'Problem',
                                    'improvement' => 'Verbesserung',
                                ])
                                ->required(),

                            Forms\Components\Textarea::make('content')
                                ->label('Inhalt')
                                ->required()
                                ->rows(4),

                            Forms\Components\Toggle::make('is_favorite')
                                ->label('Als Favorit markieren'),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sortierreihenfolge')
                                ->numeric()
                                ->default(0),
                        ])
                        ->action(function (SolarPlantNote $record, array $data) {
                            $record->update($data);
                            
                            Notification::make()
                                ->title('Notiz aktualisiert')
                                ->body('Die Notiz wurde erfolgreich aktualisiert.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggleFavorite')
                        ->label(fn (SolarPlantNote $record) => $record->is_favorite ? 'Favorit entfernen' : 'Als Favorit markieren')
                        ->icon(fn (SolarPlantNote $record) => $record->is_favorite ? 'heroicon-o-star' : 'heroicon-s-star')
                        ->color(fn (SolarPlantNote $record) => $record->is_favorite ? 'gray' : 'warning')
                        ->action(function (SolarPlantNote $record) {
                            $record->update(['is_favorite' => !$record->is_favorite]);
                            
                            Notification::make()
                                ->title($record->is_favorite ? 'Als Favorit markiert' : 'Favorit entfernt')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Notiz löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie diese Notiz löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_favorite')
                        ->label('Als Favoriten markieren')
                        ->icon('heroicon-s-star')
                        ->color('warning')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_favorite' => true]);
                            }
                            
                            Notification::make()
                                ->title('Notizen als Favoriten markiert')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('remove_favorite')
                        ->label('Favoriten entfernen')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_favorite' => false]);
                            }
                            
                            Notification::make()
                                ->title('Favoriten entfernt')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Notizen löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Notizen löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->modalSubmitActionLabel('Ja, löschen'),
                ]),
            ])
            ->defaultSort($this->showOnlyFavorites ? 'sort_order' : 'created_at', $this->showOnlyFavorites ? 'asc' : 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading($this->showOnlyFavorites ? 'Keine Favoriten-Notizen vorhanden' : 'Keine Notizen vorhanden')
            ->emptyStateDescription($this->showOnlyFavorites 
                ? 'Es wurden noch keine Notizen als Favoriten markiert. Markieren Sie wichtige Notizen mit einem Stern.'
                : 'Es wurden noch keine Notizen zu dieser Solaranlage erstellt. Erstellen Sie die erste Notiz über den Button "Neue Notiz".'
            )
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    protected function getTableName(): string
    {
        $suffix = $this->showOnlyFavorites ? 'favorites' : 'all';
        return 'notes-table-' . $this->solarPlant->id . '-' . $suffix;
    }

    public function render(): View
    {
        return view('livewire.notes-table');
    }
}
