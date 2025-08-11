<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\TaskHistory;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $title = 'Verlauf';

    protected static ?string $label = 'Änderung';

    protected static ?string $pluralLabel = 'Änderungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->label('Beschreibung')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System')
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('action')
                    ->label('Aktion')
                    ->colors([
                        'primary' => 'created',
                        'success' => 'field_changed',
                        'info' => 'note_added',
                        'warning' => 'note_updated',
                        'danger' => 'note_deleted',
                        'gray' => 'deleted',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Erstellt',
                        'field_changed' => 'Feld geändert',
                        'note_added' => 'Notiz hinzugefügt',
                        'note_updated' => 'Notiz bearbeitet',
                        'note_deleted' => 'Notiz gelöscht',
                        'deleted' => 'Gelöscht',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('field_name')
                    ->label('Feld')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->visible(fn (TaskHistory $record): bool => $record->action === 'field_changed'),

                Tables\Columns\TextColumn::make('old_value')
                    ->label('Alter Wert')
                    ->limit(50)
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? html_entity_decode($state, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '-'
                    )
                    ->html()
                    ->visible(fn (TaskHistory $record): bool => $record->action === 'field_changed'),

                Tables\Columns\TextColumn::make('new_value')
                    ->label('Neuer Wert')
                    ->limit(50)
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? html_entity_decode($state, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '-'
                    )
                    ->html()
                    ->visible(fn (TaskHistory $record): bool => $record->action === 'field_changed'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(100)
                    ->wrap()
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? html_entity_decode($state, ENT_QUOTES | ENT_HTML5, 'UTF-8') : ''
                    )
                    ->html()
                    ->tooltip(fn (TaskHistory $record): string => strip_tags(html_entity_decode($record->description ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Aktion')
                    ->options([
                        'created' => 'Erstellt',
                        'field_changed' => 'Feld geändert',
                        'note_added' => 'Notiz hinzugefügt',
                        'note_updated' => 'Notiz bearbeitet',
                        'note_deleted' => 'Notiz gelöscht',
                        'deleted' => 'Gelöscht',
                    ]),
                    
                Tables\Filters\Filter::make('created_at')
                    ->label('Zeitraum')
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
                // Keine Header-Aktionen - History ist nur lesbar
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details')
                    ->modalHeading('Verlauf Details')
                    ->modalContent(fn (TaskHistory $record): string => view('filament.task-history-details', compact('record'))->render()),
            ])
            ->bulkActions([
                // Keine Bulk-Aktionen - History ist nur lesbar
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s') // Automatisches Aktualisieren alle 30 Sekunden
            ->emptyStateHeading('Keine Änderungen')
            ->emptyStateDescription('Für diese Aufgabe wurden noch keine Änderungen protokolliert.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
