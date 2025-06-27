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

    protected static ?string $title = 'Kundenbeteiligungen';

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
                    ->required()
                    ->searchable()
                    ->preload()
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
                Forms\Components\TextInput::make('percentage')
                    ->label('Beteiligung (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%')
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->placeholder('z.B. 25.50')
                    ->helperText(function ($livewire) {
                        $solarPlant = $livewire->getOwnerRecord();
                        $available = $solarPlant->available_participation;
                        return "Verfügbar: {$available}% (Gesamt: {$solarPlant->total_participation}% von 100%)";
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
                                    $fail("Die Gesamtbeteiligung würde {$totalParticipation}% betragen. Maximal verfügbar: {$available}%");
                                }
                            };
                        },
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer.name')
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Beteiligung')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, ',', '.') . '%')
                    ->sortable()
                    ->badge()
                    ->color('success'),
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