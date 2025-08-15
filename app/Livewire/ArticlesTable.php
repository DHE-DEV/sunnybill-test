<?php

namespace App\Livewire;

use App\Models\Article;
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

class ArticlesTable extends Component implements HasForms, HasTable
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
                Article::query()
                    ->whereHas('solarPlants', function ($query) {
                        $query->where('solar_plants.id', $this->solarPlant->id);
                    })
                    ->with(['taxRate'])
            )
            ->headerActions([
                Tables\Actions\Action::make('add_article')
                    ->label('Artikel hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('article_id')
                            ->label('Artikel')
                            ->options(function () {
                                // Hole alle Artikel, die noch nicht zugeordnet sind
                                $assignedArticleIds = $this->solarPlant->articles()->pluck('articles.id')->toArray();
                                
                                return Article::query()
                                    ->whereNotIn('id', $assignedArticleIds)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($article) {
                                        $label = $article->name;
                                        if ($article->type) {
                                            $typeLabel = match($article->type) {
                                                'service' => 'Dienstleistung',
                                                'product' => 'Produkt',
                                                'material' => 'Material',
                                                'equipment' => 'Ausrüstung',
                                                'spare_part' => 'Ersatzteil',
                                                'maintenance' => 'Wartung',
                                                'other' => 'Sonstige',
                                                default => $article->type,
                                            };
                                            $label .= " ({$typeLabel})";
                                        }
                                        if ($article->price) {
                                            $label .= ' - €' . number_format($article->price, 2, ',', '.');
                                        }
                                        return [$article->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->preload()
                            ->placeholder('Artikel auswählen...')
                            ->helperText('Wählen Sie einen Artikel aus der Liste')
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $article = Article::find($state);
                                    if ($article) {
                                        // Setze Standardwerte vom Artikel
                                        $set('unit_price', $article->price);
                                        $set('unit', $article->unit);
                                    }
                                }
                            }),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix(fn (Forms\Get $get) => $get('unit') ?: 'Stück')
                                    ->placeholder('z.B. 1,00'),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Einheitspreis')
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('z.B. 100,00')
                                    ->helperText('Überschreibt den Standardpreis des Artikels'),
                            ]),
                        Forms\Components\Select::make('billing_requirement')
                            ->label('Abrechnungsart')
                            ->options([
                                'monthly' => 'Monatlich',
                                'quarterly' => 'Quartalsweise',
                                'annually' => 'Jährlich',
                                'one_time' => 'Einmalig',
                                'on_demand' => 'Bei Bedarf',
                            ])
                            ->default('one_time')
                            ->required()
                            ->helperText('Wann soll dieser Artikel abgerechnet werden?'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Artikel aktiv')
                            ->default(true)
                            ->helperText('Aktive Artikel werden bei Abrechnungen berücksichtigt'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->placeholder('Optionale Notizen zu diesem Artikel')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('unit')
                            ->default('Stück'),
                    ])
                    ->action(function (array $data) {
                        // Zuordnung des Artikels zur Solaranlage
                        $article = Article::find($data['article_id']);
                        
                        if (!$article) {
                            Notification::make()
                                ->title('Fehler')
                                ->body('Der ausgewählte Artikel konnte nicht gefunden werden.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Prüfe ob Artikel bereits zugeordnet ist
                        if ($this->solarPlant->articles()->where('articles.id', $article->id)->exists()) {
                            Notification::make()
                                ->title('Artikel bereits zugeordnet')
                                ->body('Dieser Artikel ist bereits der Solaranlage zugeordnet.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Füge Artikel zur Solaranlage hinzu
                        $this->solarPlant->articles()->attach($article->id, [
                            'quantity' => $data['quantity'] ?? 1,
                            'unit_price' => $data['unit_price'] ?? $article->price,
                            'notes' => $data['notes'] ?? null,
                            'is_active' => $data['is_active'] ?? true,
                            'billing_requirement' => $data['billing_requirement'] ?? 'one_time',
                        ]);
                        
                        Notification::make()
                            ->title('Artikel hinzugefügt')
                            ->body("Der Artikel '{$article->name}' wurde erfolgreich zur Solaranlage hinzugefügt.")
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Artikel zur Solaranlage hinzufügen')
                    ->modalDescription('Wählen Sie einen Artikel aus und konfigurieren Sie die Zuordnung.')
                    ->modalSubmitActionLabel('Artikel hinzufügen')
                    ->modalWidth('lg'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Artikelname')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.articles.view', $record))
                    ->openUrlInNewTab(false)
                    ->limit(50),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'service' => 'Dienstleistung',
                        'product' => 'Produkt',
                        'material' => 'Material',
                        'equipment' => 'Ausrüstung',
                        'spare_part' => 'Ersatzteil',
                        'maintenance' => 'Wartung',
                        'other' => 'Sonstige',
                        default => $state ?: 'Nicht angegeben',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'service' => 'info',
                        'product' => 'success',
                        'material' => 'warning',
                        'equipment' => 'primary',
                        'spare_part' => 'danger',
                        'maintenance' => 'gray',
                        'other' => 'secondary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot_quantity')
                    ->label('Menge')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment ? number_format($assignment->pivot->quantity, 2, ',', '.') : '0';
                    })
                    ->alignEnd()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Einheit')
                    ->color('gray')
                    ->placeholder('Stück'),

                Tables\Columns\TextColumn::make('pivot_unit_price')
                    ->label('Einheitspreis')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment && $assignment->pivot->unit_price 
                            ? '€ ' . number_format($assignment->pivot->unit_price, 2, ',', '.')
                            : '€ ' . number_format($record->price, 2, ',', '.');
                    })
                    ->alignEnd()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pivot_total_price')
                    ->label('Gesamtpreis')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        if ($assignment) {
                            $unitPrice = $assignment->pivot->unit_price ?: $record->price;
                            $quantity = $assignment->pivot->quantity ?: 0;
                            $total = $unitPrice * $quantity;
                            return '€ ' . number_format($total, 2, ',', '.');
                        }
                        return '€ 0,00';
                    })
                    ->alignEnd()
                    ->color('primary')
                    ->weight('medium'),

                Tables\Columns\IconColumn::make('pivot_is_active')
                    ->label('Aktiv')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment ? $assignment->pivot->is_active : false;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('pivot_billing_requirement')
                    ->label('Abrechnungsart')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment && $assignment->pivot->billing_requirement 
                            ? match($assignment->pivot->billing_requirement) {
                                'monthly' => 'Monatlich',
                                'quarterly' => 'Quartalsweise',
                                'annually' => 'Jährlich',
                                'one_time' => 'Einmalig',
                                'on_demand' => 'Bei Bedarf',
                                default => $assignment->pivot->billing_requirement,
                            }
                            : 'Nicht angegeben';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment && $assignment->pivot->billing_requirement 
                            ? match($assignment->pivot->billing_requirement) {
                                'monthly' => 'success',
                                'quarterly' => 'info',
                                'annually' => 'warning',
                                'one_time' => 'primary',
                                'on_demand' => 'gray',
                                default => 'secondary',
                            }
                            : 'gray';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('price')
                    ->label('Standardpreis')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state, 2, ',', '.'))
                    ->alignEnd()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('taxRate.name')
                    ->label('Steuersatz')
                    ->placeholder('Standard')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pivot_notes')
                    ->label('Notizen')
                    ->state(function ($record) {
                        $assignment = $record->solarPlants()
                            ->where('solar_plants.id', $this->solarPlant->id)
                            ->first();
                        return $assignment ? $assignment->pivot->notes : null;
                    })
                    ->limit(30)
                    ->placeholder('Keine Notizen')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'service' => 'Dienstleistung',
                        'product' => 'Produkt',
                        'material' => 'Material',
                        'equipment' => 'Ausrüstung',
                        'spare_part' => 'Ersatzteil',
                        'maintenance' => 'Wartung',
                        'other' => 'Sonstige',
                    ]),

                Tables\Filters\TernaryFilter::make('pivot_is_active')
                    ->label('Aktiv-Status')
                    ->placeholder('Alle Artikel')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('solarPlants', function ($q) {
                            $q->where('solar_plants.id', $this->solarPlant->id)
                              ->where('solar_plant_article.is_active', true);
                        }),
                        false: fn (Builder $query) => $query->whereHas('solarPlants', function ($q) {
                            $q->where('solar_plants.id', $this->solarPlant->id)
                              ->where('solar_plant_article.is_active', false);
                        }),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\SelectFilter::make('pivot_billing_requirement')
                    ->label('Abrechnungsart')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('solarPlants', function ($q) use ($data) {
                                $q->where('solar_plants.id', $this->solarPlant->id)
                                  ->where('solar_plant_article.billing_requirement', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->options([
                        'monthly' => 'Monatlich',
                        'quarterly' => 'Quartalsweise',
                        'annually' => 'Jährlich',
                        'one_time' => 'Einmalig',
                        'on_demand' => 'Bei Bedarf',
                    ]),

                Tables\Filters\Filter::make('price_range')
                    ->label('Preisbereich')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Von (€)')
                            ->numeric()
                            ->placeholder('Mindestpreis'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Bis (€)')
                            ->numeric()
                            ->placeholder('Höchstpreis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price', '>=', $price),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price', '<=', $price),
                            );
                    }),

                Tables\Filters\Filter::make('high_value')
                    ->label('Hochwertige Artikel')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('price', '>', 1000)
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.articles.view', $record))
                        ->openUrlInNewTab(false),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->url(fn ($record) => route('filament.admin.resources.articles.edit', $record))
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(function ($record) {
                            $assignment = $record->solarPlants()
                                ->where('solar_plants.id', $this->solarPlant->id)
                                ->first();
                            return $assignment && $assignment->pivot->is_active ? 'Deaktivieren' : 'Aktivieren';
                        })
                        ->icon(function ($record) {
                            $assignment = $record->solarPlants()
                                ->where('solar_plants.id', $this->solarPlant->id)
                                ->first();
                            return $assignment && $assignment->pivot->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
                        })
                        ->color(function ($record) {
                            $assignment = $record->solarPlants()
                                ->where('solar_plants.id', $this->solarPlant->id)
                                ->first();
                            return $assignment && $assignment->pivot->is_active ? 'danger' : 'success';
                        })
                        ->action(function ($record) {
                            $assignment = $record->solarPlants()
                                ->where('solar_plants.id', $this->solarPlant->id)
                                ->first();
                            if ($assignment) {
                                $record->solarPlants()->updateExistingPivot($this->solarPlant->id, [
                                    'is_active' => !$assignment->pivot->is_active
                                ]);
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
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->solarPlants()->updateExistingPivot($this->solarPlant->id, [
                                    'is_active' => true
                                ]);
                            }
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->solarPlants()->updateExistingPivot($this->solarPlant->id, [
                                    'is_active' => false
                                ]);
                            }
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Zuordnung entfernen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Artikel-Zuordnungen entfernen')
                        ->modalDescription('Sind Sie sicher, dass Sie die Zuordnung der ausgewählten Artikel zu dieser Solaranlage entfernen möchten? Die Artikel selbst werden nicht gelöscht.')
                        ->modalSubmitActionLabel('Ja, Zuordnungen entfernen')
                        ->successNotificationTitle('Artikel-Zuordnungen entfernt')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->solarPlants()->detach($this->solarPlant->id);
                            }
                        }),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Artikel zugeordnet')
            ->emptyStateDescription('Es wurden noch keine Artikel zu dieser Solaranlage zugeordnet.')
            ->emptyStateIcon('heroicon-o-squares-plus')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'articles-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.articles-table');
    }
}
