<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExportResource\Pages;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Exports';

    protected static ?string $modelLabel = 'Export';

    protected static ?string $pluralModelLabel = 'Exports';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('exporter')
                    ->label('Exporter')
                    ->disabled(),
                Forms\Components\TextInput::make('total_rows')
                    ->label('Gesamte Zeilen')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('successful_rows')
                    ->label('Erfolgreiche Zeilen')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('file_name')
                    ->label('Dateiname')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exporter')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'App\\Filament\\Exports\\SolarPlantExporter' => 'Solaranlagen',
                        default => class_basename($state),
                    })
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rows')
                    ->label('Zeilen')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('successful_rows')
                    ->label('Erfolgreich')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('failed_rows_count')
                    ->label('Fehlgeschlagen')
                    ->getStateUsing(fn ($record) => $record->total_rows - $record->successful_rows)
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Datei')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->file_name),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Abgeschlossen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('In Bearbeitung...'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\Filter::make('completed')
                    ->label('Nur abgeschlossene')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('completed_at')),
                Tables\Filters\Filter::make('failed')
                    ->label('Mit Fehlern')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('total_rows > successful_rows')),
                Tables\Filters\SelectFilter::make('exporter')
                    ->label('Export-Typ')
                    ->options([
                        'App\\Filament\\Exports\\SolarPlantExporter' => 'Solaranlagen',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Export $record) {
                        if (!$record->completed_at) {
                            \Filament\Notifications\Notification::make()
                                ->title('Export noch nicht abgeschlossen')
                                ->body('Der Export ist noch in Bearbeitung. Bitte warten Sie, bis er abgeschlossen ist.')
                                ->warning()
                                ->send();
                            return;
                        }

                        if (!$record->file_name) {
                            \Filament\Notifications\Notification::make()
                                ->title('Keine Datei verfügbar')
                                ->body('Für diesen Export ist keine Datei verfügbar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $disk = \Storage::disk($record->file_disk);
                        
                        if (!$disk->exists($record->file_name)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Datei nicht gefunden')
                                ->body('Die Export-Datei wurde nicht gefunden oder ist bereits gelöscht.')
                                ->danger()
                                ->send();
                            return;
                        }

                        return response()->download(
                            $disk->path($record->file_name),
                            basename($record->file_name)
                        );
                    })
                    ->visible(fn (Export $record) => $record->completed_at !== null && $record->file_name !== null),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Export $record) {
                        // Lösche auch die Datei
                        if ($record->file_name) {
                            try {
                                $disk = \Storage::disk($record->file_disk);
                                if ($disk->exists($record->file_name)) {
                                    $disk->delete($record->file_name);
                                }
                            } catch (\Exception $e) {
                                // Ignoriere Fehler beim Löschen der Datei
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Lösche auch die Dateien
                            foreach ($records as $record) {
                                if ($record->file_name) {
                                    try {
                                        $disk = \Storage::disk($record->file_disk);
                                        if ($disk->exists($record->file_name)) {
                                            $disk->delete($record->file_name);
                                        }
                                    } catch (\Exception $e) {
                                        // Ignoriere Fehler beim Löschen der Datei
                                    }
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExports::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id()); // Nur eigene Exports anzeigen
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }
}
