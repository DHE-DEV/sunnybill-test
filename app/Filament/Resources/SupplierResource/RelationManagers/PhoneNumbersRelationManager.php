<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\PhoneNumber;
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
                            ->maxLength(255)
                            ->placeholder('+49 123 456789'),
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->required()
                            ->options([
                                'business' => 'Geschäftlich',
                                'private' => 'Privat',
                                'mobile' => 'Mobil',
                            ])
                            ->default('business'),
                        Forms\Components\TextInput::make('label')
                            ->label('Beschreibung')
                            ->maxLength(255)
                            ->placeholder('z.B. Zentrale, Direktwahl, etc.'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Hauptnummer')
                            ->helperText('Nur eine Nummer kann als Hauptnummer markiert werden.')
                            ->default(false),
                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Favorit')
                            ->helperText('Favoriten werden oben in der Liste angezeigt.')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone_number')
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Telefonnummer kopiert!')
                    ->formatStateUsing(fn (PhoneNumber $record) => $record->formatted_number)
                    ->url(fn (PhoneNumber $record) => $record->phone_number ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone_number) : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (PhoneNumber $record) => $record->getTypeLabel())
                    ->colors([
                        'primary' => 'business',
                        'success' => 'mobile',
                        'warning' => 'private',
                    ]),
                Tables\Columns\TextColumn::make('label')
                    ->label('Beschreibung')
                    ->placeholder('Keine Beschreibung')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorit')
                    ->boolean()
                    ->trueIcon('heroicon-o-heart')
                    ->falseIcon('heroicon-o-heart')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable()
                    ->action(
                        Tables\Actions\Action::make('toggle_favorite')
                            ->action(function (PhoneNumber $record): void {
                                $record->update(['is_favorite' => !$record->is_favorite]);
                            })
                    ),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Hauptnummer')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
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
                    ]),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Hauptnummer'),
                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favoriten'),
            ])
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Telefonnummer hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['phoneable_type'] = $this->getOwnerRecord()::class;
                        $data['phoneable_id'] = $this->getOwnerRecord()->id;
                        
                        // Automatische sort_order Vergabe
                        $maxSortOrder = $this->getOwnerRecord()
                            ->phoneNumbers()
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        
                        return $data;
                    }),
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
            ->defaultSort(fn ($query) => $query->ordered())
            ->emptyStateHeading('Keine Telefonnummern vorhanden')
            ->emptyStateDescription('Fügen Sie die erste Telefonnummer für diesen Lieferanten hinzu.')
            ->emptyStateIcon('heroicon-o-phone');
    }
}