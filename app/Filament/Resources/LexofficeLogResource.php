<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LexofficeLogResource\Pages;
use App\Filament\Resources\LexofficeLogResource\RelationManagers;
use App\Models\LexofficeLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LexofficeLogResource extends Resource
{
    protected static ?string $model = LexofficeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Lexoffice Logs';
    
    protected static ?string $modelLabel = 'Lexoffice Log';
    
    protected static ?string $pluralModelLabel = 'Lexoffice Logs';
    
    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Log-Details')
                    ->schema([
                        Forms\Components\TextInput::make('type')
                            ->label('Typ')
                            ->disabled(),
                        Forms\Components\TextInput::make('action')
                            ->label('Aktion')
                            ->disabled(),
                        Forms\Components\TextInput::make('entity_id')
                            ->label('Entitäts-ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('lexoffice_id')
                            ->label('Lexoffice ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Daten')
                    ->schema([
                        Forms\Components\Textarea::make('request_data')
                            ->label('Anfrage-Daten')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')
                            ->disabled()
                            ->rows(10),
                        Forms\Components\Textarea::make('response_data')
                            ->label('Antwort-Daten')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')
                            ->disabled()
                            ->rows(10),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Fehlermeldung')
                            ->disabled()
                            ->rows(3)
                            ->visible(fn ($record) => $record?->error_message),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'customer' => 'Kunde',
                        'article' => 'Artikel',
                        'invoice' => 'Rechnung',
                        default => $state
                    })
                    ->colors([
                        'info' => 'customer',
                        'warning' => 'article',
                        'success' => 'invoice',
                    ]),
                Tables\Columns\BadgeColumn::make('action')
                    ->label('Aktion')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'import' => 'Import',
                        'export' => 'Export',
                        'sync' => 'Sync',
                        default => $state
                    })
                    ->colors([
                        'primary' => 'import',
                        'secondary' => 'export',
                        'info' => 'sync',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'success' => 'Erfolgreich',
                        'error' => 'Fehler',
                        default => $state
                    })
                    ->colors([
                        'success' => 'success',
                        'danger' => 'error',
                    ]),
                Tables\Columns\TextColumn::make('entity_id')
                    ->label('Entitäts-ID')
                    ->limit(8)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lexoffice_id')
                    ->label('Lexoffice ID')
                    ->limit(8)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Fehler')
                    ->limit(50)
                    ->toggleable()
                    ->visible(fn ($record) => $record?->status === 'error'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'customer' => 'Kunde',
                        'article' => 'Artikel',
                        'invoice' => 'Rechnung',
                    ]),
                Tables\Filters\SelectFilter::make('action')
                    ->label('Aktion')
                    ->options([
                        'import' => 'Import',
                        'export' => 'Export',
                        'sync' => 'Sync',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Erfolgreich',
                        'error' => 'Fehler',
                    ]),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLexofficeLogs::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
}
