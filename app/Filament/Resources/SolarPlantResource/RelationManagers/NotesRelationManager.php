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
use Illuminate\Support\Facades\Auth;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->maxLength(255)
                            ->placeholder('Kurzer Titel für die Notiz'),
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->required()
                            ->options([
                                'general' => 'Allgemein',
                                'maintenance' => 'Wartung',
                                'issue' => 'Problem',
                                'improvement' => 'Verbesserung',
                            ])
                            ->default('general'),
                    ]),
                Forms\Components\Textarea::make('content')
                    ->label('Inhalt')
                    ->required()
                    ->rows(5)
                    ->placeholder('Detaillierte Beschreibung der Notiz...')
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
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
                        'general' => 'gray',
                        'maintenance' => 'warning',
                        'issue' => 'danger',
                        'improvement' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('Ohne Titel'),
                Tables\Columns\TextColumn::make('short_content')
                    ->label('Inhalt')
                    ->searchable(['content'])
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Erstellt von')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('d.m.Y H:i:s')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
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
                Tables\Filters\SelectFilter::make('user')
                    ->label('Erstellt von')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Notiz hinzufügen')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Notiz hinzugefügt')
                            ->body('Die Notiz wurde erfolgreich erstellt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen'),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->is_admin ?? false)
                    ->after(function () {
                        Notification::make()
                            ->title('Notiz aktualisiert')
                            ->body('Die Notiz wurde erfolgreich geändert.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->user_id === Auth::id() || Auth::user()->is_admin ?? false)
                    ->after(function () {
                        Notification::make()
                            ->title('Notiz gelöscht')
                            ->body('Die Notiz wurde erfolgreich entfernt.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->is_admin ?? false),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Notizen')
            ->emptyStateDescription('Fügen Sie Notizen hinzu, um wichtige Informationen zu dieser Solaranlage zu dokumentieren.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}