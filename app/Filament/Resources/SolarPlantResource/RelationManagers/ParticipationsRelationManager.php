<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class ParticipationsRelationManager extends RelationManager
{
    protected static string $relationship = 'participations';

    protected static ?string $title = 'Beteiligungen';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->participations()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                    ->required()
                    ->searchable(['name', 'company_name'])
                    ->preload()
                    ->columnSpanFull()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon'),
                    ]),
                Forms\Components\Section::make('Beteiligungsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('participation_kwp')
                            ->label('Beteiligung kWp')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->suffix('kWp')
                            ->placeholder('z.B. 25.0000')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                if ($state && $state > 0) {
                                    $solarPlant = $livewire->getOwnerRecord();
                                    if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                        $percentage = ($state / $solarPlant->total_capacity_kw) * 100;
                                        $set('percentage', round($percentage, 4));
                                    }
                                }
                            })
                            ->helperText(function ($livewire) {
                                $solarPlant = $livewire->getOwnerRecord();
                                return $solarPlant ? "Anlagenkapazität: " . number_format($solarPlant->total_capacity_kw ?? 0, 4, ',', '.') . " kWp" : '';
                            }),
                        
                        Forms\Components\TextInput::make('percentage')
                            ->label('Beteiligung (%)')
                            ->required()
                            ->numeric()
                            ->step(0.0001)
                            ->suffix('%')
                            ->minValue(0.0001)
                            ->maxValue(100)
                            ->placeholder('z.B. 25.5000')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                if ($state && $state > 0) {
                                    $solarPlant = $livewire->getOwnerRecord();
                                    if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                        $kwp = ($state / 100) * $solarPlant->total_capacity_kw;
                                        $set('participation_kwp', round($kwp, 4));
                                    }
                                }
                            })
                            ->helperText(function ($livewire) {
                                $solarPlant = $livewire->getOwnerRecord();
                                $available = $solarPlant->available_participation ?? 0;
                                return "Verfügbar: " . number_format($available, 4, ',', '.') . "% (Gesamt: " . number_format($solarPlant->total_participation ?? 0, 4, ',', '.') . "% von 100%)";
                            })
                            ->rules([
                                function ($livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                        $solarPlant = $livewire->getOwnerRecord();
                                        $currentRecord = $livewire->mountedTableActionRecord ?? null;
                                        
                                        $existingParticipation = $solarPlant->participations()
                                            ->where('id', '!=', $currentRecord?->id ?? 0)
                                            ->sum('percentage');
                                        
                                        $totalParticipation = $existingParticipation + $value;
                                        
                                        if ($totalParticipation > 100) {
                                            $available = 100 - $existingParticipation;
                                            $fail("Die Gesamtbeteiligung würde " . number_format($totalParticipation, 4, ',', '.') . "% betragen. Maximal verfügbar: " . number_format($available, 4, ',', '.') . "%");
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('calculate_from_kwp')
                                ->label('Aus kWp berechnen')
                                ->icon('heroicon-m-calculator')
                                ->color('info')
                                ->action(function (Forms\Set $set, array $data, $livewire) {
                                    $kwp = $data['participation_kwp'] ?? 0;
                                    if ($kwp && $kwp > 0) {
                                        $solarPlant = $livewire->getOwnerRecord();
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $percentage = ($kwp / $solarPlant->total_capacity_kw) * 100;
                                            $set('percentage', round($percentage, 4));
                                        }
                                    }
                                }),
                            Forms\Components\Actions\Action::make('calculate_from_percentage')
                                ->label('Aus % berechnen')
                                ->icon('heroicon-m-calculator')
                                ->color('success')
                                ->action(function (Forms\Set $set, array $data, $livewire) {
                                    $percentage = $data['percentage'] ?? 0;
                                    if ($percentage && $percentage > 0) {
                                        $solarPlant = $livewire->getOwnerRecord();
                                        if ($solarPlant && $solarPlant->total_capacity_kw > 0) {
                                            $kwp = ($percentage / 100) * $solarPlant->total_capacity_kw;
                                            $set('participation_kwp', round($kwp, 4));
                                        }
                                    }
                                }),
                        ])
                        ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\TextInput::make('eeg_compensation_per_kwh')
                    ->label('Vertraglich zugesicherte EEG-Vergütung')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(0)
                    ->suffix('€/kWh')
                    ->placeholder('0,000000')
                    ->helperText('Vergütung pro kWh in EUR mit bis zu 6 Nachkommastellen'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer.name')
            ->columns([
                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label('Kunde')
                    ->searchable(['customer.name', 'customer.company_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('participation_kwp')
                    ->label('kWp')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 4, ',', '.') . ' kWp' : '-')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 4, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('eeg_compensation_per_kwh')
                    ->label('EEG-Vergütung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 6, ',', '.') . ' €/kWh' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Beteiligung seit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('high_participation')
                    ->label('Hohe Beteiligung (>= 25%)')
                    ->query(fn (Builder $query): Builder => $query->where('percentage', '>=', 25)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Beteiligung hinzufügen')
                    ->after(function () {
                        Notification::make()
                            ->title('Beteiligung hinzugefügt')
                            ->body('Die Kundenbeteiligung wurde erfolgreich erstellt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_customer')
                    ->label('Anzeigen')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.customers.view', ['record' => $record->customer_id]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Beteiligung aktualisiert')
                            ->body('Die Kundenbeteiligung wurde erfolgreich geändert.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Beteiligung entfernt')
                            ->body('Die Kundenbeteiligung wurde erfolgreich gelöscht.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('percentage', 'desc')
            ->emptyStateHeading('Keine Beteiligungen')
            ->emptyStateDescription('Fügen Sie Kundenbeteiligungen hinzu, um die Erträge zu verteilen.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
