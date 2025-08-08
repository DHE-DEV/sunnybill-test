<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\PhoneNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhoneNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'phoneNumbers';

    protected static ?string $title = 'Telefonnummern';

    protected static ?string $modelLabel = 'Telefonnummer';

    protected static ?string $pluralModelLabel = 'Telefonnummern';

    protected static ?string $icon = 'heroicon-o-phone';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'private' => 'Privat',
                                'business' => 'Geschäftlich', 
                                'mobile' => 'Mobil',
                                'fax' => 'Fax',
                                'home' => 'Zu Hause',
                                'work' => 'Arbeit',
                                'other' => 'Sonstiges',
                            ])
                            ->required()
                            ->default('business')
                            ->helperText('Art der Telefonnummer'),

                        Forms\Components\TextInput::make('label')
                            ->label('Bezeichnung')
                            ->placeholder('z.B. Hauptnummer, Notfall, Sekretariat')
                            ->maxLength(255)
                            ->helperText('Optionale Bezeichnung für bessere Identifikation'),
                    ]),

                Forms\Components\TextInput::make('phone_number')
                    ->label('Telefonnummer')
                    ->tel()
                    ->required()
                    ->maxLength(50)
                    ->placeholder('+49 30 12345678')
                    ->helperText('Telefonnummer im internationalen Format'),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Hauptnummer')
                            ->helperText('Diese Nummer als Hauptnummer markieren')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $record) {
                                if ($state && $record) {
                                    // Warnung anzeigen, wenn bereits eine Hauptnummer existiert
                                    $existingPrimary = $this->getOwnerRecord()
                                        ->phoneNumbers()
                                        ->where('is_primary', true)
                                        ->where('id', '!=', $record->id)
                                        ->exists();
                                    
                                    if ($existingPrimary) {
                                        Notification::make()
                                            ->title('Hauptnummer wird überschrieben')
                                            ->body('Die bisherige Hauptnummer wird automatisch deaktiviert.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->live(),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Favorit')
                            ->helperText('Als Favorit markieren für schnellen Zugriff'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Reihenfolge')
                            ->numeric()
                            ->default(fn () => $this->getOwnerRecord()->phoneNumbers()->max('sort_order') + 1 ?? 1)
                            ->helperText('Anzeigereihenfolge (niedrigere Zahlen zuerst)'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'private' => 'Privat',
                        'business' => 'Geschäftlich',
                        'mobile' => 'Mobil',
                        'fax' => 'Fax',
                        'home' => 'Zu Hause',
                        'work' => 'Arbeit',
                        'other' => 'Sonstiges',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'private' => 'info',
                        'business' => 'success',
                        'mobile' => 'warning',
                        'fax' => 'gray',
                        'home' => 'purple',
                        'work' => 'indigo',
                        'other' => 'slate',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Bezeichnung')
                    ->placeholder('Keine Bezeichnung')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert!')
                    ->tooltip('Klicken zum Kopieren'),

                Tables\Columns\TextColumn::make('formatted_number')
                    ->label('Formatiert')
                    ->toggleable()
                    ->tooltip('Automatisch formatierte Anzeige'),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Haupt')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->tooltip(fn ($record) => $record->is_primary ? 'Hauptnummer' : '')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorit')
                    ->boolean()
                    ->trueIcon('heroicon-s-heart')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->tooltip(fn ($record) => $record->is_favorite ? 'Favorit' : '')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Reihenfolge')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hinzugefügt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'private' => 'Privat',
                        'business' => 'Geschäftlich',
                        'mobile' => 'Mobil',
                        'fax' => 'Fax',
                        'home' => 'Zu Hause',
                        'work' => 'Arbeit',
                        'other' => 'Sonstiges',
                    ]),

                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Hauptnummer')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Hauptnummern')
                    ->falseLabel('Keine Hauptnummern'),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favoriten')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Favoriten')
                    ->falseLabel('Keine Favoriten'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Telefonnummer hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Neue Telefonnummer hinzufügen')
                    ->successNotificationTitle('Telefonnummer hinzugefügt')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Wenn dies die erste Telefonnummer ist, automatisch als Hauptnummer markieren
                        if (empty($data['is_primary']) && $this->getOwnerRecord()->phoneNumbers()->count() === 0) {
                            $data['is_primary'] = true;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalHeading('Telefonnummer bearbeiten')
                        ->successNotificationTitle('Telefonnummer aktualisiert'),

                    Tables\Actions\Action::make('call')
                        ->label('Anrufen')
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->url(fn (PhoneNumber $record) => 'tel:' . $record->phone_number)
                        ->openUrlInNewTab(false),

                    Tables\Actions\Action::make('copy')
                        ->label('Kopieren')
                        ->icon('heroicon-o-clipboard')
                        ->color('gray')
                        ->action(function (PhoneNumber $record) {
                            // JavaScript wird verwendet um in die Zwischenablage zu kopieren
                            $this->js("navigator.clipboard.writeText('{$record->phone_number}')");
                            
                            Notification::make()
                                ->title('Telefonnummer kopiert')
                                ->body("Die Nummer {$record->phone_number} wurde in die Zwischenablage kopiert.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('set_primary')
                        ->label('Als Hauptnummer setzen')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn (PhoneNumber $record) => !$record->is_primary)
                        ->requiresConfirmation()
                        ->modalHeading('Als Hauptnummer setzen')
                        ->modalDescription(fn (PhoneNumber $record) => "Möchten Sie '{$record->display_label}' als neue Hauptnummer setzen?")
                        ->action(function (PhoneNumber $record) {
                            $record->update(['is_primary' => true]);
                            
                            Notification::make()
                                ->title('Hauptnummer aktualisiert')
                                ->body("'{$record->display_label}' ist jetzt die Hauptnummer.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_favorite')
                        ->label(fn (PhoneNumber $record) => $record->is_favorite ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen')
                        ->icon(fn (PhoneNumber $record) => $record->is_favorite ? 'heroicon-s-heart' : 'heroicon-o-heart')
                        ->color(fn (PhoneNumber $record) => $record->is_favorite ? 'danger' : 'gray')
                        ->action(function (PhoneNumber $record) {
                            $record->update(['is_favorite' => !$record->is_favorite]);
                            
                            $message = $record->is_favorite ? 'Zu Favoriten hinzugefügt' : 'Aus Favoriten entfernt';
                            
                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Telefonnummer löschen')
                        ->modalDescription(fn (PhoneNumber $record) => "Sind Sie sicher, dass Sie die Telefonnummer '{$record->display_label}' löschen möchten?")
                        ->successNotificationTitle('Telefonnummer gelöscht')
                        ->before(function (PhoneNumber $record) {
                            // Wenn die Hauptnummer gelöscht wird, eine andere zur Hauptnummer machen
                            if ($record->is_primary) {
                                $nextPrimary = $this->getOwnerRecord()
                                    ->phoneNumbers()
                                    ->where('id', '!=', $record->id)
                                    ->orderBy('is_favorite', 'desc')
                                    ->orderBy('sort_order')
                                    ->first();

                                if ($nextPrimary) {
                                    $nextPrimary->update(['is_primary' => true]);
                                    
                                    Notification::make()
                                        ->title('Neue Hauptnummer')
                                        ->body("'{$nextPrimary->display_label}' ist jetzt die neue Hauptnummer.")
                                        ->info()
                                        ->send();
                                }
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
                    Tables\Actions\BulkAction::make('set_favorite')
                        ->label('Als Favoriten markieren')
                        ->icon('heroicon-o-heart')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_favorite' => true]);
                            
                            Notification::make()
                                ->title(count($records) . ' Telefonnummern als Favoriten markiert')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('remove_favorite')
                        ->label('Aus Favoriten entfernen')
                        ->icon('heroicon-s-heart')
                        ->color('gray')
                        ->action(function ($records) {
                            $records->each->update(['is_favorite' => false]);
                            
                            Notification::make()
                                ->title(count($records) . ' Telefonnummern aus Favoriten entfernt')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Telefonnummern löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Telefonnummern löschen möchten?'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Keine Telefonnummern')
            ->emptyStateDescription('Fügen Sie die erste Telefonnummer für diesen Benutzer hinzu.')
            ->emptyStateIcon('heroicon-o-phone')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Erste Telefonnummer hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
