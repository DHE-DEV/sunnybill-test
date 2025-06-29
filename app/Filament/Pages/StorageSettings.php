<?php

namespace App\Filament\Pages;

use App\Models\StorageSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class StorageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static string $view = 'filament.pages.storage-settings';

    protected static ?string $title = 'Speicher-Einstellungen';

    protected static ?string $navigationLabel = 'Speicher-Einstellungen';

    protected static ?string $navigationGroup = 'Einstellungen';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $setting = StorageSetting::current();
        
        if ($setting) {
            $this->form->fill([
                'storage_driver' => $setting->storage_driver,
                'storage_config' => $setting->storage_config ?? [],
            ]);
        } else {
            $this->form->fill([
                'storage_driver' => 'local',
                'storage_config' => [],
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Speicher-Konfiguration')
                    ->description('Wählen Sie den gewünschten Speicher-Anbieter und konfigurieren Sie die Verbindung.')
                    ->schema([
                        Forms\Components\Select::make('storage_driver')
                            ->label('Speicher-Anbieter')
                            ->options(StorageSetting::getDriverOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('storage_config', [])),

                        Forms\Components\Group::make([
                            Forms\Components\Placeholder::make('local_info')
                                ->label('Lokaler Speicher')
                                ->content('Dateien werden im lokalen Dateisystem des Servers gespeichert.')
                                ->visible(fn (Forms\Get $get) => $get('storage_driver') === 'local'),
                        ]),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('storage_config.key')
                                ->label('AWS Access Key ID')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 's3')
                                ->password()
                                ->revealable(),

                            Forms\Components\TextInput::make('storage_config.secret')
                                ->label('AWS Secret Access Key')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 's3')
                                ->password()
                                ->revealable(),

                            Forms\Components\TextInput::make('storage_config.region')
                                ->label('AWS Region')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 's3')
                                ->placeholder('eu-central-1'),

                            Forms\Components\TextInput::make('storage_config.bucket')
                                ->label('S3 Bucket Name')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 's3'),

                            Forms\Components\TextInput::make('storage_config.url')
                                ->label('Custom URL (optional)')
                                ->url()
                                ->placeholder('https://your-bucket.s3.amazonaws.com'),

                            Forms\Components\TextInput::make('storage_config.endpoint')
                                ->label('Custom Endpoint (optional)')
                                ->url()
                                ->placeholder('https://s3.amazonaws.com'),

                            Forms\Components\Toggle::make('storage_config.use_path_style_endpoint')
                                ->label('Path Style Endpoint verwenden')
                                ->helperText('Aktivieren für MinIO oder andere S3-kompatible Services'),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('storage_driver') === 's3')
                        ->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('storage_config.key')
                                ->label('DigitalOcean Spaces Access Key')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                                ->password()
                                ->revealable(),

                            Forms\Components\TextInput::make('storage_config.secret')
                                ->label('DigitalOcean Spaces Secret Key')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                                ->password()
                                ->revealable(),

                            Forms\Components\Select::make('storage_config.region')
                                ->label('DigitalOcean Region')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                                ->options([
                                    'nyc3' => 'New York 3',
                                    'ams3' => 'Amsterdam 3',
                                    'sgp1' => 'Singapore 1',
                                    'fra1' => 'Frankfurt 1',
                                    'sfo3' => 'San Francisco 3',
                                    'blr1' => 'Bangalore 1',
                                    'syd1' => 'Sydney 1',
                                ])
                                ->searchable(),

                            Forms\Components\TextInput::make('storage_config.bucket')
                                ->label('Space Name')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                                ->helperText('Der Name Ihres DigitalOcean Space'),

                            Forms\Components\TextInput::make('storage_config.endpoint')
                                ->label('Endpoint URL')
                                ->required(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                                ->placeholder('https://fra1.digitaloceanspaces.com')
                                ->helperText('Format: https://[region].digitaloceanspaces.com'),

                            Forms\Components\TextInput::make('storage_config.url')
                                ->label('CDN URL (optional)')
                                ->url()
                                ->placeholder('https://your-space.fra1.cdn.digitaloceanspaces.com')
                                ->helperText('Für bessere Performance mit CDN'),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('storage_driver') === 'digitalocean')
                        ->columns(2),
                    ]),

                Forms\Components\Section::make('Speicher-Statistiken')
                    ->description('Übersicht über den aktuellen Speicherverbrauch.')
                    ->schema([
                        Forms\Components\Placeholder::make('total_size')
                            ->label('Gesamtgröße')
                            ->content(function () {
                                $setting = StorageSetting::current();
                                return $setting ? $setting->formatted_storage_used : '0 B';
                            }),

                        Forms\Components\Placeholder::make('document_count')
                            ->label('Anzahl Dokumente')
                            ->content(function () {
                                return \App\Models\Document::count();
                            }),

                        Forms\Components\Placeholder::make('last_calculation')
                            ->label('Letzte Berechnung')
                            ->content(function () {
                                $setting = StorageSetting::current();
                                return $setting && $setting->last_storage_calculation
                                    ? $setting->last_storage_calculation->format('d.m.Y H:i')
                                    : 'Nie';
                            }),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Verbindung testen')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testConnection')
                ->visible(fn () => $this->data['storage_driver'] !== 'local'),

            Action::make('calculate_storage')
                ->label('Speicher neu berechnen')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->action('calculateStorage'),

            Action::make('save')
                ->label('Speichern')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }

    public function testConnection(): void
    {
        try {
            // Daten aus dem Formular holen ohne Validierung
            $data = $this->form->getRawState();
            
            // Debug: Daten loggen
            \Log::info('testConnection Debug', [
                'raw_data' => $data,
                'storage_driver' => $data['storage_driver'] ?? 'not set',
                'storage_config' => $data['storage_config'] ?? 'not set',
                'region_from_config' => $data['storage_config']['region'] ?? 'not set'
            ]);
            
            if (empty($data['storage_driver'])) {
                Notification::make()
                    ->title('Konfigurationsfehler')
                    ->body('Bitte wählen Sie einen Speicher-Anbieter aus.')
                    ->danger()
                    ->send();
                return;
            }
            
            $tempSetting = new StorageSetting([
                'storage_driver' => $data['storage_driver'],
                'storage_config' => $data['storage_config'] ?? [],
            ]);

            $errors = $tempSetting->validateConfig();
            if (!empty($errors)) {
                Notification::make()
                    ->title('Konfigurationsfehler')
                    ->body(implode(', ', $errors))
                    ->danger()
                    ->send();
                return;
            }

            $result = $tempSetting->testConnection();

            if ($result['success']) {
                Notification::make()
                    ->title('Verbindung erfolgreich')
                    ->body($result['message'])
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Verbindung fehlgeschlagen')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Testen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function calculateStorage(): void
    {
        try {
            $setting = StorageSetting::current();
            if (!$setting) {
                Notification::make()
                    ->title('Keine Einstellungen gefunden')
                    ->body('Bitte speichern Sie zuerst die Speicher-Einstellungen.')
                    ->warning()
                    ->send();
                return;
            }

            $totalSize = $setting->calculateTotalStorage();
            $formattedSize = $setting->formatBytes($totalSize);

            Notification::make()
                ->title('Speicher neu berechnet')
                ->body("Gesamtgröße: {$formattedSize}")
                ->success()
                ->send();

            // Seite neu laden um aktualisierte Werte anzuzeigen
            $this->redirect(static::getUrl());
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler bei der Berechnung')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        try {
            // Daten ohne Validierung holen, damit immer gespeichert werden kann
            $data = $this->form->getRawState();

            // Alle bestehenden Einstellungen deaktivieren
            StorageSetting::query()->update(['is_active' => false]);

            // Neue Einstellung erstellen oder aktualisieren
            $setting = StorageSetting::create([
                'storage_driver' => $data['storage_driver'] ?? 'local',
                'storage_config' => $data['storage_config'] ?? [],
                'is_active' => true,
            ]);

            // Speicher berechnen
            $setting->calculateTotalStorage();

            Notification::make()
                ->title('Einstellungen gespeichert')
                ->body('Die Speicher-Einstellungen wurden erfolgreich gespeichert. Verwenden Sie "Verbindung testen" um die Konfiguration zu überprüfen.')
                ->success()
                ->send();

            // Seite neu laden
            $this->redirect(static::getUrl());
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}