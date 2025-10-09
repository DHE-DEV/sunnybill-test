<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SimCardResource\Pages;
use App\Models\SimCard;
use App\Models\Router;
use App\Services\OnceApiService;
use App\Jobs\RefreshSimCardDataJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimCardResource extends Resource
{
    protected static ?string $model = SimCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'SIM Karten';

    protected static ?string $modelLabel = 'SIM Karte';

    protected static ?string $pluralModelLabel = 'SIM Karten';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Netzwerk Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('iccid')
                                            ->label('ICCID')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->placeholder('z.B. 8949102100000000001')
                                            ->helperText('International Circuit Card Identifier - eindeutige SIM-Karten-Nummer'),
                                        Forms\Components\TextInput::make('msisdn')
                                            ->label('Telefonnummer (MSISDN)')
                                            ->maxLength(255)
                                            ->placeholder('z.B. +4915123456789')
                                            ->helperText('Mobile Station International Subscriber Directory Number'),
                                        Forms\Components\TextInput::make('imsi')
                                            ->label('IMSI')
                                            ->maxLength(255)
                                            ->placeholder('z.B. 262011234567890')
                                            ->helperText('International Mobile Subscriber Identity'),
                                        Forms\Components\TextInput::make('assigned_to')
                                            ->label('Zugewiesen an')
                                            ->maxLength(255)
                                            ->placeholder('z.B. Router A1, Mitarbeiter Max Mustermann')
                                            ->helperText('Gerät oder Person, dem die SIM-Karte zugewiesen ist'),
                                    ]),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('pin_code')
                                            ->label('PIN Code')
                                            ->maxLength(255)
                                            ->placeholder('1234')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\TextInput::make('puk_code')
                                            ->label('PUK Code')
                                            ->maxLength(255)
                                            ->placeholder('12345678')
                                            ->password()
                                            ->revealable(),
                                        Forms\Components\Select::make('router_id')
                                            ->label('Router')
                                            ->relationship('router', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Optional: Router dem diese SIM-Karte zugewiesen ist'),
                                    ]),
                                Forms\Components\TextInput::make('location')
                                    ->label('Standort')
                                    ->maxLength(255)
                                    ->placeholder('z.B. Musterstraße 1, 12345 Musterstadt')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(3)
                                    ->placeholder('Zusätzliche Informationen zur SIM-Karte...')
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Provider & Vertrag')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Provider Informationen')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('provider')
                                                    ->label('Anbieter')
                                                    ->options(SimCard::getProviderOptions())
                                                    ->required()
                                                    ->searchable(),
                                                Forms\Components\TextInput::make('tariff')
                                                    ->label('Tarif')
                                                    ->maxLength(255)
                                                    ->placeholder('z.B. DataConnect L'),
                                            ]),
                                    ]),
                                Forms\Components\Section::make('Vertragsdaten')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('contract_type')
                                                    ->label('Vertragsart')
                                                    ->options(SimCard::getContractTypeOptions())
                                                    ->default('postpaid')
                                                    ->required(),
                                                Forms\Components\TextInput::make('monthly_cost')
                                                    ->label('Monatliche Kosten')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->step(0.01)
                                                    ->placeholder('9.99'),
                                                Forms\Components\TextInput::make('apn')
                                                    ->label('APN')
                                                    ->maxLength(255)
                                                    ->placeholder('z.B. internet.telekom')
                                                    ->helperText('Access Point Name für Datenverbindung'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('contract_start')
                                                    ->label('Vertragsbeginn')
                                                    ->displayFormat('d.m.Y'),
                                                Forms\Components\DatePicker::make('contract_end')
                                                    ->label('Vertragsende')
                                                    ->displayFormat('d.m.Y')
                                                    ->helperText('Optional: Laufzeit des Vertrags'),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Datenverbrauch')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Datenvolumen')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('data_limit_mb')
                                                    ->label('Datenlimit (MB)')
                                                    ->numeric()
                                                    ->placeholder('1024')
                                                    ->suffix('MB')
                                                    ->helperText('Monatliches Datenvolumen in MB (leer = unbegrenzt)'),
                                                Forms\Components\TextInput::make('data_used_mb')
                                                    ->label('Verbrauchte Daten (MB)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->suffix('MB')
                                                    ->helperText('Datenverbrauch des aktuellen Monats'),
                                            ]),
                                        Forms\Components\DateTimePicker::make('last_activity')
                                            ->label('Letzte Aktivität')
                                            ->displayFormat('d.m.Y H:i:s')
                                            ->helperText('Zeitpunkt der letzten Datennutzung'),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('SIM-Karte aktiv')
                                            ->default(true)
                                            ->helperText('Bestimmt, ob die SIM-Karte zur Nutzung aktiviert ist'),
                                        Forms\Components\Toggle::make('is_blocked')
                                            ->label('SIM-Karte gesperrt')
                                            ->default(false)
                                            ->helperText('Sperrt die SIM-Karte für alle Dienste'),
                                    ]),
                                Forms\Components\Select::make('status')
                                    ->label('Betriebsstatus')
                                    ->options(SimCard::getStatusOptions())
                                    ->default('active')
                                    ->required()
                                    ->helperText('Aktueller Betriebsstatus der SIM-Karte'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->searchable(['msisdn', 'iccid'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('msisdn', $direction)
                                     ->orderBy('iccid', $direction);
                    })
                    ->weight('bold')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Anbieter')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status_text')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('status', $direction)
                                     ->orderBy('is_blocked', $direction);
                    }),
                Tables\Columns\TextColumn::make('formatted_signal_strength')
                    ->label('Signalstärke')
                    ->badge()
                    ->color(fn ($record) => $record->signal_strength_color)
                    ->placeholder('-')
                    ->tooltip(fn ($record) => $record->signal_strength_quality)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('signal_strength', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Vertragsart')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'prepaid' => 'Prepaid',
                        'postpaid' => 'Vertrag',
                        'iot' => 'IoT/M2M',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'prepaid' => 'warning',
                        'postpaid' => 'success',
                        'iot' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('formatted_data_usage')
                    ->label('Datenverbrauch')
                    ->badge()
                    ->color(fn ($record) => $record->data_usage_color)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('monthly_cost')
                    ->label('Monatl. Kosten')
                    ->money('EUR')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('router.name')
                    ->label('Router')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('msisdn')
                    ->label('Telefonnummer')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->limit(30)
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contract_end')
                    ->label('Vertragsende')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable()
                    ->color(fn ($record) => $record->contract_status_color)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_activity_formatted')
                    ->label('Letzte Aktivität')
                    ->placeholder('Nie')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('last_activity', $direction);
                    }),
                Tables\Columns\TextColumn::make('iccid')
                    ->label('ICCID')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Bezeichnung')
                    ->searchable(['assigned_to', 'msisdn', 'iccid'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('assigned_to', $direction)
                                     ->orderBy('msisdn', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Anbieter')
                    ->options(SimCard::getProviderOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SimCard::getStatusOptions()),
                Tables\Filters\SelectFilter::make('contract_type')
                    ->label('Vertragsart')
                    ->options(SimCard::getContractTypeOptions()),
                Tables\Filters\Filter::make('is_active')
                    ->label('Nur aktive SIM-Karten')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('is_blocked')
                    ->label('Gesperrte SIM-Karten')
                    ->query(fn (Builder $query): Builder => $query->where('is_blocked', true)),
                Tables\Filters\Filter::make('with_router')
                    ->label('Mit Router')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('router_id')),
                Tables\Filters\Filter::make('contract_expiring')
                    ->label('Vertrag läuft ab (30 Tage)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('contract_end')
                              ->whereBetween('contract_end', [now(), now()->addDays(30)])
                    ),
                Tables\Filters\Filter::make('data_limit_exceeded')
                    ->label('Datenlimit überschritten')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('data_limit_mb')
                              ->whereColumn('data_used_mb', '>=', 'data_limit_mb')
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('reset_data_usage')
                        ->label('Datenverbrauch zurücksetzen')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Datenverbrauch zurücksetzen')
                        ->modalDescription('Möchten Sie den monatlichen Datenverbrauch auf 0 MB zurücksetzen?')
                        ->action(fn (SimCard $record) => $record->resetMonthlyDataUsage())
                        ->successNotificationTitle('Datenverbrauch wurde zurückgesetzt'),
                    Tables\Actions\Action::make('toggle_block')
                        ->label(fn ($record) => $record->is_blocked ? 'Entsperren' : 'Sperren')
                        ->icon(fn ($record) => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                        ->color(fn ($record) => $record->is_blocked ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => ($record->is_blocked ? 'SIM-Karte entsperren' : 'SIM-Karte sperren'))
                        ->modalDescription(fn ($record) => ($record->is_blocked 
                            ? 'Möchten Sie die SIM-Karte entsperren und wieder aktivieren?' 
                            : 'Möchten Sie die SIM-Karte sperren? Dies blockiert alle Dienste.'))
                        ->action(fn (SimCard $record) => $record->update(['is_blocked' => !$record->is_blocked]))
                        ->successNotificationTitle(fn ($record) => $record->is_blocked ? 'SIM-Karte wurde gesperrt' : 'SIM-Karte wurde entsperrt'),
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
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('CSV Export')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            try {
                                $csv = [];
                                $csv[] = [
                                    'ICCID', 'Telefonnummer (MSISDN)', 'IMSI', 'Anbieter', 'Tarif', 'Status',
                                    'Vertragsart', 'Zugewiesen an', 'Router', 'Standort', 'Monatl. Kosten',
                                    'Datenlimit (MB)', 'Verbrauch (MB)', 'Signalstärke', 'Vertragsbeginn',
                                    'Vertragsende', 'Letzte Aktivität', 'Aktiv', 'Gesperrt', 'Erstellt am'
                                ];

                                foreach ($records as $sim) {
                                    $csv[] = [
                                        $sim->iccid ?? '',
                                        $sim->msisdn ?? '',
                                        $sim->imsi ?? '',
                                        $sim->provider ?? '',
                                        $sim->tariff ?? '',
                                        match($sim->status) {
                                            'active' => 'Aktiv',
                                            'inactive' => 'Inaktiv',
                                            'suspended' => 'Suspendiert',
                                            'terminated' => 'Gekündigt',
                                            default => $sim->status
                                        },
                                        match($sim->contract_type) {
                                            'prepaid' => 'Prepaid',
                                            'postpaid' => 'Vertrag',
                                            'iot' => 'IoT/M2M',
                                            default => $sim->contract_type
                                        },
                                        $sim->assigned_to ?? '',
                                        $sim->router?->name ?? '',
                                        $sim->location ?? '',
                                        $sim->monthly_cost ? number_format($sim->monthly_cost, 2, ',', '.') : '',
                                        $sim->data_limit_mb ?? '',
                                        $sim->data_used_mb ?? '0',
                                        $sim->signal_strength ?? '',
                                        $sim->contract_start ? $sim->contract_start->format('d.m.Y') : '',
                                        $sim->contract_end ? $sim->contract_end->format('d.m.Y') : '',
                                        $sim->last_activity ? $sim->last_activity->format('d.m.Y H:i:s') : '',
                                        $sim->is_active ? 'Aktiv' : 'Inaktiv',
                                        $sim->is_blocked ? 'Ja' : 'Nein',
                                        $sim->created_at ? $sim->created_at->format('d.m.Y H:i') : '',
                                    ];
                                }

                                $filename = 'sim-karten-' . now()->format('Y-m-d_H-i-s') . '.csv';
                                $tempPath = 'temp/csv-exports/' . $filename;
                                \Storage::disk('public')->makeDirectory('temp/csv-exports');
                                $output = fopen('php://temp', 'r+');
                                fputs($output, "\xEF\xBB\xBF");
                                foreach ($csv as $row) {
                                    fputcsv($output, $row, ';');
                                }
                                rewind($output);
                                \Storage::disk('public')->put($tempPath, stream_get_contents($output));
                                fclose($output);

                                session(['csv_download_path' => $tempPath, 'csv_download_filename' => $filename]);

                                Notification::make()
                                    ->title('CSV-Export erfolgreich')
                                    ->body('Klicken Sie auf den Button, um die Datei herunterzuladen.')
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('download')
                                            ->label('Datei herunterladen')
                                            ->url(route('admin.download-csv'))
                                            ->openUrlInNewTab()
                                            ->button()
                                    ])
                                    ->persistent()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Fehler beim CSV-Export')
                                    ->body('Ein Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('CSV Export')
                        ->modalDescription(fn (Collection $records) => "Möchten Sie die " . $records->count() . " ausgewählten SIM-Karten als CSV-Datei exportieren?")
                        ->modalSubmitActionLabel('CSV exportieren')
                        ->modalIcon('heroicon-o-document-arrow-down'),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('reset_data_usage_bulk')
                        ->label('Datenverbrauch zurücksetzen')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->resetMonthlyDataUsage();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Datenverbrauch wurde für alle ausgewählten SIM-Karten zurückgesetzt'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine SIM-Karten')
            ->emptyStateDescription('Erstellen Sie Ihre erste SIM-Karte für die Verwaltung.')
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
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
            'index' => Pages\ListSimCards::route('/'),
            'create' => Pages\CreateSimCard::route('/create'),
            'edit' => Pages\EditSimCard::route('/{record}/edit'),
        ];
    }

    public static function getHeaderActions(): array
    {
        return [
            Action::make('refresh_status')
                ->label('Status aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('SIM-Karten Status aktualisieren')
                ->modalDescription('Möchten Sie den Status, die Signalstärke und letzte Aktivität aller SIM-Karten von der 1nce API aktualisieren?')
                ->modalSubmitActionLabel('Ja, aktualisieren')
                ->action(function () {
                    try {
                        $job = new RefreshSimCardDataJob();
                        $result = $job->handle();
                        
                        if ($result['success']) {
                            $message = "Status-Aktualisierung erfolgreich:\n";
                            $message .= "• {$result['updated']} SIM-Karten aktualisiert\n";
                            if ($result['errors'] > 0) {
                                $message .= "• {$result['errors']} Fehler aufgetreten";
                            }
                            
                            Notification::make()
                                ->title('Status aktualisiert')
                                ->body($message)
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Aktualisierung fehlgeschlagen')
                                ->body($result['message'] ?? 'Ein unbekannter Fehler ist aufgetreten.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Log::error('Manual SIM card refresh failed', ['error' => $e->getMessage()]);
                        
                        Notification::make()
                            ->title('Fehler beim Aktualisieren')
                            ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('import')
                ->label('Importieren')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('SIM Karten von 1nce importieren')
                ->modalDescription('Möchten Sie SIM Karten von der 1nce API importieren? Bestehende SIM Karten werden basierend auf der ICCID aktualisiert.')
                ->modalSubmitActionLabel('Ja, importieren')
                ->action(function () {
                    try {
                        $onceService = new OnceApiService();
                        
                        // Test connection first
                        if (!$onceService->testConnection()) {
                            Notification::make()
                                ->title('Verbindungsfehler')
                                ->body('Verbindung zur 1nce API konnte nicht hergestellt werden. Bitte überprüfen Sie Ihre API-Credentials.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $simCards = $onceService->getSimCards();

                        if (empty($simCards)) {
                            Notification::make()
                                ->title('Keine Daten')
                                ->body('Keine SIM Karten von der 1nce API erhalten.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $imported = 0;
                        $updated = 0;
                        $errors = 0;

                        DB::transaction(function () use ($simCards, &$imported, &$updated, &$errors) {
                            foreach ($simCards as $simData) {
                                try {
                                    if (empty($simData['iccid'])) {
                                        $errors++;
                                        Log::warning('1nce Import: SIM card without ICCID skipped', $simData);
                                        continue;
                                    }

                                    $existingSimCard = SimCard::where('iccid', $simData['iccid'])->first();

                                    if ($existingSimCard) {
                                        $existingSimCard->update($simData);
                                        $updated++;
                                        Log::info('1nce Import: Updated SIM card', ['iccid' => $simData['iccid']]);
                                    } else {
                                        SimCard::create($simData);
                                        $imported++;
                                        Log::info('1nce Import: Created new SIM card', ['iccid' => $simData['iccid']]);
                                    }
                                } catch (\Exception $e) {
                                    $errors++;
                                    Log::error('1nce Import: Error processing SIM card', [
                                        'iccid' => $simData['iccid'] ?? 'unknown',
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        });

                        if ($imported > 0 || $updated > 0) {
                            $message = "Import erfolgreich abgeschlossen:\n";
                            if ($imported > 0) {
                                $message .= "• {$imported} neue SIM Karten importiert\n";
                            }
                            if ($updated > 0) {
                                $message .= "• {$updated} SIM Karten aktualisiert\n";
                            }
                            if ($errors > 0) {
                                $message .= "• {$errors} Fehler aufgetreten";
                            }

                            Notification::make()
                                ->title('Import erfolgreich')
                                ->body($message)
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import abgebrochen')
                                ->body($errors > 0 ? "Import fehlgeschlagen: {$errors} Fehler aufgetreten." : 'Keine Daten zum Importieren gefunden.')
                                ->warning()
                                ->send();
                        }

                    } catch (\Exception $e) {
                        Log::error('1nce Import: General error', ['error' => $e->getMessage()]);
                        
                        Notification::make()
                            ->title('Import Fehler')
                            ->body('Ein unerwarteter Fehler ist aufgetreten: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
}
