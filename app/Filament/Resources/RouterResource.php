<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouterResource\Pages;
use App\Models\Router;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RouterResource extends Resource
{
    protected static ?string $model = Router::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Router';

    protected static ?string $modelLabel = 'Router';

    protected static ?string $pluralModelLabel = 'Router';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Netzwerk Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Router Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. RUTX50 Standort A'),
                                        Forms\Components\Select::make('model')
                                            ->label('Modell')
                                            ->options(Router::getModelOptions())
                                            ->default('RUTX50')
                                            ->required(),
                                        Forms\Components\TextInput::make('serial_number')
                                            ->label('Seriennummer')
                                            ->maxLength(255)
                                            ->placeholder('z.B. ABC123DEF456'),
                                        Forms\Components\TextInput::make('ip_address')
                                            ->label('IP-Adresse')
                                            ->maxLength(45)
                                            ->placeholder('z.B. 192.168.1.100'),
                                        Forms\Components\TextInput::make('webhook_port')
                                            ->label('Webhook Port')
                                            ->numeric()
                                            ->default(3000)
                                            ->minValue(1)
                                            ->maxValue(65535),
                                        Forms\Components\TextInput::make('webhook_token')
                                            ->label('Webhook Token')
                                            ->disabled()
                                            ->helperText('Automatisch generierter Sicherheitstoken'),
                                    ]),
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('location')
                                            ->label('Standort')
                                            ->maxLength(255)
                                            ->placeholder('z.B. Musterstraße 1, 12345 Musterstadt')
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->placeholder('Zusätzliche Informationen zum Router...')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Standort')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Geokoordinaten')
                                    ->description('Genaue Position des Routers für Kartendarstellung')
                                    ->schema([
                                        Forms\Components\TextInput::make('coordinates_input')
                                            ->label('Koordinaten einfügen')
                                            ->placeholder('Koordinaten hier einfügen (z.B. 51.419, 7.041)')
                                            ->helperText(new \Illuminate\Support\HtmlString('Kopieren Sie Koordinaten von <a href="https://www.google.com/maps" target="_blank" class="text-primary-600 hover:text-primary-500 underline">Google Maps</a> und fügen Sie sie hier ein. Die Werte werden automatisch aufgeteilt.'))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                                if (empty($state)) {
                                                    return;
                                                }
                                                
                                                $coords = trim($state);
                                                
                                                if (preg_match('/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/', $coords, $matches)) {
                                                    $latitude = (float) $matches[1];
                                                    $longitude = (float) $matches[2];
                                                    
                                                    $set('latitude', $latitude);
                                                    $set('longitude', $longitude);
                                                    
                                                    $set('coordinates_input', '');
                                                    
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Koordinaten übertragen')
                                                        ->body("Breitengrad: {$latitude}, Längengrad: {$longitude}")
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Ungültiges Format')
                                                        ->body('Bitte verwenden Sie das Format: Breitengrad, Längengrad (z.B. 51.419, 7.041)')
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('latitude')
                                                    ->label('Breitengrad (Latitude)')
                                                    ->numeric()
                                                    ->step('any')
                                                    ->placeholder('z.B. 52.520008')
                                                    ->suffix('°N')
                                                    ->helperText('Dezimalgrad (WGS84)'),
                                                Forms\Components\TextInput::make('longitude')
                                                    ->label('Längengrad (Longitude)')
                                                    ->numeric()
                                                    ->step('any')
                                                    ->placeholder('z.B. 13.404954')
                                                    ->suffix('°E')
                                                    ->helperText('Dezimalgrad (WGS84)'),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Netzwerk')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\Section::make('Aktuelle Netzwerkdaten')
                                    ->description('Diese Daten werden automatisch über Webhooks aktualisiert')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('operator')
                                                    ->label('Netzbetreiber')
                                                    ->disabled()
                                                    ->placeholder('Wird automatisch aktualisiert'),
                                                Forms\Components\TextInput::make('network_type')
                                                    ->label('Netzwerk Typ')
                                                    ->disabled()
                                                    ->placeholder('z.B. 4G, 5G'),
                                                Forms\Components\TextInput::make('signal_strength')
                                                    ->label('Signalstärke (dBm)')
                                                    ->disabled()
                                                    ->suffix('dBm')
                                                    ->placeholder('-65'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('total_webhooks')
                                                    ->label('Empfangene Webhooks')
                                                    ->disabled()
                                                    ->numeric(),
                                                Forms\Components\DateTimePicker::make('last_seen_at')
                                                    ->label('Zuletzt gesehen')
                                                    ->disabled()
                                                    ->displayFormat('d.m.Y H:i:s'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('last_restart_at')
                                                    ->label('Letzter Neustart')
                                                    ->disabled()
                                                    ->displayFormat('d.m.Y H:i:s')
                                                    ->placeholder('Noch nie neu gestartet'),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Router aktiv')
                                            ->default(true)
                                            ->helperText('Bestimmt, ob der Router zur Überwachung aktiv ist'),
                                        Forms\Components\Select::make('connection_status')
                                            ->label('Verbindungsstatus')
                                            ->options(Router::getStatusOptions())
                                            ->disabled()
                                            ->helperText('Status wird automatisch basierend auf der letzten Aktivität berechnet'),
                                    ]),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->rows(4)
                                    ->placeholder('Zusätzliche Notizen zum Router...')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Modell')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('connection_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'online' => 'Online',
                        'delayed' => 'Verzögert',
                        'offline' => 'Offline',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'online' => 'success',
                        'delayed' => 'warning',
                        'offline' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('operator')
                    ->label('Netzbetreiber')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('network_type')
                    ->label('Netzwerk')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        '5G' => 'success',
                        '4G' => 'primary',
                        '3G' => 'warning',
                        '2G' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('signal_strength')
                    ->label('Signal')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '-';
                        $bars = $record->calculateSignalBars();
                        $barsDisplay = str_repeat('▰', $bars) . str_repeat('▱', 5 - $bars);
                        return $state . ' dBm ' . $barsDisplay;
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->limit(30)
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP-Adresse')
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Seriennummer')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_webhooks')
                    ->label('Webhooks')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_seen_formatted')
                    ->label('Zuletzt gesehen')
                    ->placeholder('Nie')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('last_seen_at', $direction);
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('connection_status')
                    ->label('Verbindungsstatus')
                    ->options(Router::getStatusOptions()),
                Tables\Filters\SelectFilter::make('model')
                    ->label('Modell')
                    ->options(Router::getModelOptions()),
                Tables\Filters\Filter::make('is_active')
                    ->label('Nur aktive Router')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('has_location')
                    ->label('Mit Standort')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('location')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test_webhook')
                        ->label('Webhook testen')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->modalHeading(fn ($record) => 'Webhook Test für ' . $record->name)
                        ->modalContent(fn ($record) => view('filament.actions.router-webhook-test', [
                            'router' => $record,
                            'webhookUrl' => $record->webhook_url,
                            'curlCommand' => $record->test_curl_command,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Schließen'),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Router')
            ->emptyStateDescription('Erstellen Sie Ihren ersten Router für die Überwachung.')
            ->emptyStateIcon('heroicon-o-signal');
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
            'index' => Pages\ListRouters::route('/'),
            'create' => Pages\CreateRouter::route('/create'),
            'edit' => Pages\EditRouter::route('/{record}/edit'),
        ];
    }
}
