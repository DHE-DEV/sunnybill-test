<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhoneNumbersRelationManager extends RelationManager
{
    protected static string $relationship = 'phoneNumbers';

    protected static ?string $title = 'Telefonnummern';

    protected static ?string $modelLabel = 'Telefonnummer';

    protected static ?string $pluralModelLabel = 'Telefonnummern';

    protected static ?string $icon = 'heroicon-o-phone';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Telefonnummer')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Telefonnummer')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'business' => 'Geschäftlich',
                                'private' => 'Privat',
                                'mobile' => 'Mobil',
                                'fax' => 'Fax',
                                'other' => 'Sonstiges',
                            ])
                            ->default('business')
                            ->required(),
                        Forms\Components\TextInput::make('label')
                            ->label('Beschreibung')
                            ->maxLength(255)
                            ->placeholder('z.B. Zentrale, Direktwahl, etc.'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Hauptnummer')
                            ->default(false),
                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Favorit')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('')
                    ->icon('heroicon-s-star')
                    ->color('warning')
                    ->tooltip('Hauptnummer')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon(fn ($record) => $record->is_favorite ? 'heroicon-s-heart' : 'heroicon-o-heart')
                    ->color(fn ($record) => $record->is_favorite ? 'danger' : 'gray')
                    ->action(function ($record) {
                        $record->update(['is_favorite' => !$record->is_favorite]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title($record->is_favorite ? 'Zu Favoriten hinzugefügt' : 'Aus Favoriten entfernt')
                            ->success()
                            ->send();
                    })
                    ->tooltip(fn ($record) => $record->is_favorite ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->url(fn ($record) => $record->phone_number ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone_number) : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'business' => 'Geschäftlich',
                        'private' => 'Privat',
                        'mobile' => 'Mobil',
                        'fax' => 'Fax',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'business' => 'primary',
                        'private' => 'success',
                        'mobile' => 'warning',
                        'fax' => 'info',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('label')
                    ->label('Beschreibung')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'business' => 'Geschäftlich',
                        'private' => 'Privat',
                        'mobile' => 'Mobil',
                        'fax' => 'Fax',
                        'other' => 'Sonstiges',
                    ]),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Hauptnummer'),
                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favorit'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Telefonnummer hinzufügen')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_primary', 'desc')
            ->emptyStateHeading('Keine Telefonnummern vorhanden')
            ->emptyStateDescription('Fügen Sie die erste Telefonnummer für diesen Kunden hinzu.')
            ->emptyStateIcon('heroicon-o-phone');
    }
}