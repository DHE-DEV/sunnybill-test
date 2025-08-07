<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppTokenResource\Pages;
use App\Filament\Resources\AppTokenResource\RelationManagers;
use App\Models\AppToken;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Services\AppTokenQrCodeService;

class AppTokenResource extends Resource
{
    protected static ?string $model = AppToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Benutzer';

    protected static ?string $modelLabel = 'App-Token';

    protected static ?string $pluralModelLabel = 'App-Tokens';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Token-Informationen')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Benutzer')
                            ->options(User::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id()),

                        Forms\Components\TextInput::make('name')
                            ->label('Token-Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. "iPhone App", "Desktop Client"')
                            ->hint('Eindeutiger Name für diesen Token'),

                        Forms\Components\Select::make('app_type')
                            ->label('App-Typ')
                            ->options(AppToken::getAppTypes())
                            ->required()
                            ->default('mobile_app'),

                        Forms\Components\TextInput::make('app_version')
                            ->label('App-Version')
                            ->maxLength(20)
                            ->placeholder('z.B. "1.0.0"'),
                    ])->columns(2),

                Section::make('Berechtigungen')
                    ->description('Granulare Rechteverwaltung für den API-Token')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Spalte 1: Aufgaben
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Aufgaben-Verwaltung')
                                            ->description('Grundlegende CRUD-Operationen für Aufgaben')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('task_management_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'tasks:read' => 'Aufgaben lesen',
                                                        'tasks:create' => 'Aufgaben erstellen',
                                                        'tasks:update' => 'Aufgaben bearbeiten',
                                                        'tasks:delete' => 'Aufgaben löschen',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Aufgaben-Aktionen')
                                            ->description('Spezifische Aktionen und Funktionen')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('task_actions_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'tasks:assign' => 'Aufgaben zuweisen',
                                                        'tasks:status' => 'Status ändern',
                                                        'tasks:notes' => 'Notizen verwalten',
                                                        'tasks:documents' => 'Dokumente verwalten',
                                                        'tasks:time' => 'Zeiten erfassen',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Solaranlagen-Verwaltung')
                                            ->description('Verwaltung von Solaranlagen')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('solar_plants_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'solar-plants:read' => 'Solaranlagen lesen',
                                                        'solar-plants:create' => 'Solaranlagen erstellen',
                                                        'solar-plants:update' => 'Solaranlagen bearbeiten',
                                                        'solar-plants:delete' => 'Solaranlagen löschen',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),
                                    ]),

                                // Spalte 2: Ressourcen-Verwaltung
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Kunden-Verwaltung')
                                            ->description('Verwaltung von Kunden')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('customers_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'customers:read' => 'Kunden lesen',
                                                        'customers:create' => 'Kunden erstellen',
                                                        'customers:update' => 'Kunden bearbeiten',
                                                        'customers:delete' => 'Kunden löschen',
                                                        'customers:status' => 'Kunden-Status ändern',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Lieferanten-Verwaltung')
                                            ->description('Verwaltung von Lieferanten')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('suppliers_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'suppliers:read' => 'Lieferanten lesen',
                                                        'suppliers:create' => 'Lieferanten erstellen',
                                                        'suppliers:update' => 'Lieferanten bearbeiten',
                                                        'suppliers:delete' => 'Lieferanten löschen',
                                                        'suppliers:status' => 'Lieferanten-Status ändern',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Projekt-Verwaltung')
                                            ->description('Verwaltung von Projekten')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('projects_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'projects:read' => 'Projekte lesen',
                                                        'projects:create' => 'Projekte erstellen',
                                                        'projects:update' => 'Projekte bearbeiten',
                                                        'projects:delete' => 'Projekte löschen',
                                                        'projects:status' => 'Projekt-Status ändern',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),
                                    ]),

                                // Spalte 3: Erweiterte Funktionen
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Section::make('Meilensteine & Termine')
                                            ->description('Projektmeilensteine und Termine')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('milestones_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'milestones:read' => 'Meilensteine lesen',
                                                        'milestones:create' => 'Meilensteine erstellen',
                                                        'milestones:update' => 'Meilensteine bearbeiten',
                                                        'milestones:delete' => 'Meilensteine löschen',
                                                        'milestones:status' => 'Meilenstein-Status ändern',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),

                                                Forms\Components\CheckboxList::make('appointments_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'appointments:read' => 'Termine lesen',
                                                        'appointments:create' => 'Termine erstellen',
                                                        'appointments:update' => 'Termine bearbeiten',
                                                        'appointments:delete' => 'Termine löschen',
                                                        'appointments:status' => 'Termin-Status ändern',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Kosten & System')
                                            ->description('Kostenmanagement und Systemfunktionen')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('costs_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'costs:read' => 'Kosten lesen',
                                                        'costs:create' => 'Kosten erstellen',
                                                        'costs:reports' => 'Kostenberichte',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),

                                                Forms\Components\CheckboxList::make('user_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'user:profile' => 'Profil lesen',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),

                                                Forms\Components\CheckboxList::make('notification_abilities')
                                                    ->label('')
                                                    ->options([
                                                        'notifications:read' => 'Benachrichtigungen lesen',
                                                        'notifications:create' => 'Benachrichtigungen erstellen',
                                                    ])
                                                    ->columns(1)
                                                    ->bulkToggleable(),
                                            ])
                                            ->collapsible()
                                            ->persistCollapsed(),

                                        Forms\Components\Section::make('Schnell-Auswahl')
                                            ->description('Vordefinierte Berechtigungssets')
                                            ->schema([
                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('set_read_only')
                                                        ->label('Nur Lesen')
                                                        ->icon('heroicon-m-eye')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->action(function (callable $set) {
                                                            $set('task_management_abilities', ['tasks:read']);
                                                            $set('task_actions_abilities', []);
                                                            $set('solar_plants_abilities', ['solar-plants:read']);
                                                            $set('customers_abilities', ['customers:read']);
                                                            $set('suppliers_abilities', ['suppliers:read']);
                                                            $set('projects_abilities', ['projects:read']);
                                                            $set('milestones_abilities', ['milestones:read']);
                                                            $set('appointments_abilities', ['appointments:read']);
                                                            $set('costs_abilities', ['costs:read']);
                                                            $set('user_abilities', ['user:profile']);
                                                            $set('notification_abilities', ['notifications:read']);
                                                        }),

                                                    Forms\Components\Actions\Action::make('set_extended')
                                                        ->label('Erweiterte Berechtigungen')
                                                        ->icon('heroicon-m-bolt')
                                                        ->color('warning')
                                                        ->size('sm')
                                                        ->action(function (callable $set) {
                                                            $set('task_management_abilities', ['tasks:read', 'tasks:create', 'tasks:update', 'tasks:delete']);
                                                            $set('task_actions_abilities', ['tasks:assign', 'tasks:status', 'tasks:notes', 'tasks:documents', 'tasks:time']);
                                                            $set('solar_plants_abilities', ['solar-plants:read', 'solar-plants:create', 'solar-plants:update', 'solar-plants:delete']);
                                                            $set('customers_abilities', ['customers:read', 'customers:create', 'customers:update', 'customers:delete', 'customers:status']);
                                                            $set('suppliers_abilities', ['suppliers:read', 'suppliers:create', 'suppliers:update', 'suppliers:delete', 'suppliers:status']);
                                                            $set('projects_abilities', ['projects:read', 'projects:create', 'projects:update', 'projects:delete', 'projects:status']);
                                                            $set('milestones_abilities', ['milestones:read', 'milestones:create', 'milestones:update', 'milestones:delete', 'milestones:status']);
                                                            $set('appointments_abilities', ['appointments:read', 'appointments:create', 'appointments:update', 'appointments:delete', 'appointments:status']);
                                                            $set('costs_abilities', ['costs:read', 'costs:create', 'costs:reports']);
                                                            $set('user_abilities', ['user:profile']);
                                                            $set('notification_abilities', ['notifications:read', 'notifications:create']);
                                                        }),

                                                    Forms\Components\Actions\Action::make('clear_all')
                                                        ->label('Alle abwählen')
                                                        ->icon('heroicon-m-x-mark')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->action(function (callable $set) {
                                                            $set('task_management_abilities', []);
                                                            $set('task_actions_abilities', []);
                                                            $set('solar_plants_abilities', []);
                                                            $set('customers_abilities', []);
                                                            $set('suppliers_abilities', []);
                                                            $set('projects_abilities', []);
                                                            $set('milestones_abilities', []);
                                                            $set('appointments_abilities', []);
                                                            $set('costs_abilities', []);
                                                            $set('user_abilities', []);
                                                            $set('notification_abilities', []);
                                                        }),
                                                ])
                                                ->alignCenter(),
                                            ]),
                                    ]),
                            ]),

                        // Hidden field to merge all abilities into the main abilities field
                        Forms\Components\Hidden::make('abilities')
                            ->dehydrateStateUsing(function (callable $get) {
                                return array_merge(
                                    $get('task_management_abilities') ?? [],
                                    $get('task_actions_abilities') ?? [],
                                    $get('solar_plants_abilities') ?? [],
                                    $get('customers_abilities') ?? [],
                                    $get('suppliers_abilities') ?? [],
                                    $get('projects_abilities') ?? [],
                                    $get('milestones_abilities') ?? [],
                                    $get('appointments_abilities') ?? [],
                                    $get('costs_abilities') ?? [],
                                    $get('user_abilities') ?? [],
                                    $get('notification_abilities') ?? []
                                );
                            }),
                    ]),

                Section::make('Ressourcen-Beschränkungen')
                    ->description('Schränken Sie den Zugriff dieses Tokens auf bestimmte Kunden, Lieferanten, Solaranlagen oder Projekte ein.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('restrict_customers')
                                            ->label('Kunden-Zugriff beschränken')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('allowed_customers', null);
                                                }
                                            }),

                                        Forms\Components\Select::make('allowed_customers')
                                            ->label('Erlaubte Kunden')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(Customer::orderBy('name')->pluck('name', 'id'))
                                            ->visible(fn (callable $get) => $get('restrict_customers'))
                                            ->hint('Leer lassen = alle Kunden erlaubt'),
                                    ]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('restrict_suppliers')
                                            ->label('Lieferanten-Zugriff beschränken')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('allowed_suppliers', null);
                                                }
                                            }),

                                        Forms\Components\Select::make('allowed_suppliers')
                                            ->label('Erlaubte Lieferanten')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(Supplier::orderBy('name')->pluck('name', 'id'))
                                            ->visible(fn (callable $get) => $get('restrict_suppliers'))
                                            ->hint('Leer lassen = alle Lieferanten erlaubt'),
                                    ]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('restrict_solar_plants')
                                            ->label('Solaranlagen-Zugriff beschränken')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('allowed_solar_plants', null);
                                                }
                                            }),

                                        Forms\Components\Select::make('allowed_solar_plants')
                                            ->label('Erlaubte Solaranlagen')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(SolarPlant::orderBy('name')->pluck('name', 'id'))
                                            ->visible(fn (callable $get) => $get('restrict_solar_plants'))
                                            ->hint('Leer lassen = alle Solaranlagen erlaubt'),
                                    ]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('restrict_projects')
                                            ->label('Projekt-Zugriff beschränken')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (!$state) {
                                                    $set('allowed_projects', null);
                                                }
                                            }),

                                        Forms\Components\Select::make('allowed_projects')
                                            ->label('Erlaubte Projekte')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options([]) // TODO: Project model implementieren
                                            ->visible(fn (callable $get) => $get('restrict_projects'))
                                            ->hint('Leer lassen = alle Projekte erlaubt'),
                                    ]),
                            ]),
                    ]),

                Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('device_info')
                            ->label('Geräteinformationen')
                            ->rows(3)
                            ->placeholder('z.B. "iPhone 12 Pro, iOS 15.0"'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->placeholder('Zusätzliche Informationen zu diesem Token'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Token aktiv')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Token-Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('app_type_label')
                    ->label('App-Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mobile App' => 'info',
                        'Desktop App' => 'success',
                        'Web App' => 'primary',
                        'Third Party' => 'warning',
                        'Integration' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('abilities')
                    ->label('Berechtigungen')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->formatStateUsing(function ($state) {
                        $abilities = AppToken::getAvailableAbilities();
                        return $abilities[$state] ?? $state;
                    }),

                TextColumn::make('resource_restrictions')
                    ->label('Ressourcen-Beschränkungen')
                    ->formatStateUsing(function (AppToken $record): string {
                        $restrictions = [];
                        if ($record->restrict_customers) {
                            $count = count($record->allowed_customers ?? []);
                            $restrictions[] = "Kunden ({$count})";
                        }
                        if ($record->restrict_suppliers) {
                            $count = count($record->allowed_suppliers ?? []);
                            $restrictions[] = "Lieferanten ({$count})";
                        }
                        if ($record->restrict_solar_plants) {
                            $count = count($record->allowed_solar_plants ?? []);
                            $restrictions[] = "Anlagen ({$count})";
                        }
                        if ($record->restrict_projects) {
                            $count = count($record->allowed_projects ?? []);
                            $restrictions[] = "Projekte ({$count})";
                        }
                        
                        return $restrictions ? implode(', ', $restrictions) : 'Keine Beschränkungen';
                    })
                    ->badge()
                    ->color(function (AppToken $record): string {
                        $hasRestrictions = $record->restrict_customers || 
                                         $record->restrict_suppliers || 
                                         $record->restrict_solar_plants || 
                                         $record->restrict_projects;
                        return $hasRestrictions ? 'warning' : 'success';
                    })
                    ->tooltip(function (AppToken $record): ?string {
                        $details = [];
                        if ($record->restrict_customers && $record->allowed_customers) {
                            $customers = Customer::whereIn('id', $record->allowed_customers)->pluck('name')->take(5)->toArray();
                            $details[] = 'Kunden: ' . implode(', ', $customers) . (count($record->allowed_customers) > 5 ? '...' : '');
                        }
                        if ($record->restrict_suppliers && $record->allowed_suppliers) {
                            $suppliers = Supplier::whereIn('id', $record->allowed_suppliers)->pluck('name')->take(5)->toArray();
                            $details[] = 'Lieferanten: ' . implode(', ', $suppliers) . (count($record->allowed_suppliers) > 5 ? '...' : '');
                        }
                        if ($record->restrict_solar_plants && $record->allowed_solar_plants) {
                            $plants = SolarPlant::whereIn('id', $record->allowed_solar_plants)->pluck('name')->take(5)->toArray();
                            $details[] = 'Solaranlagen: ' . implode(', ', $plants) . (count($record->allowed_solar_plants) > 5 ? '...' : '');
                        }
                        
                        return $details ? implode("\n", $details) : null;
                    }),

                BadgeColumn::make('status_label')
                    ->label('Status')
                    ->color(fn (AppToken $record): string => $record->status_color),

                TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn (AppToken $record): string => $record->expires_at < now()->addDays(30) ? 'danger' : 'success'),

                TextColumn::make('last_used_at')
                    ->label('Zuletzt verwendet')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nie verwendet'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Benutzer')
                    ->options(User::active()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('app_type')
                    ->label('App-Typ')
                    ->options(AppToken::getAppTypes()),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Deaktiviert',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon()),

                Tables\Filters\Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Action::make('show_qr_code')
                    ->label('QR-Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading('Token QR-Code')
                    ->modalSubheading(fn (AppToken $record) => "QR-Code für Token: {$record->name}")
                    ->modalContent(function (AppToken $record) {
                        $qrCodeService = new AppTokenQrCodeService();
                        
                        // Hinweis: Wir können den echten Token nicht mehr anzeigen, da er gehasht ist
                        // Stattdessen zeigen wir eine Meldung
                        $warningMessage = "
                            <div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4'>
                                <div class='flex items-center'>
                                    <svg class='w-5 h-5 text-yellow-400 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                                        <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                                    </svg>
                                    <span class='text-yellow-800 font-medium'>Sicherheitshinweis</span>
                                </div>
                                <p class='text-yellow-700 mt-2'>
                                    Der QR-Code kann nur bei der Token-Erstellung angezeigt werden, da der Token aus Sicherheitsgründen verschlüsselt gespeichert wird.
                                </p>
                            </div>
                        ";
                        
                        $infoMessage = "
                            <div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>
                                <div class='flex items-center'>
                                    <svg class='w-5 h-5 text-blue-400 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                                        <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'></path>
                                    </svg>
                                    <span class='text-blue-800 font-medium'>Token-Informationen</span>
                                </div>
                                <div class='text-blue-700 mt-2 space-y-1'>
                                    <p><strong>Name:</strong> {$record->name}</p>
                                    <p><strong>App-Typ:</strong> {$record->app_type_label}</p>
                                    <p><strong>Berechtigungen:</strong> " . implode(', ', $record->abilities_labels) . "</p>
                                    <p><strong>Erstellt:</strong> " . $record->created_at->format('d.m.Y H:i') . "</p>
                                    <p><strong>Läuft ab:</strong> " . $record->expires_at->format('d.m.Y H:i') . "</p>
                                </div>
                            </div>
                        ";
                        
                        return new \Illuminate\Support\HtmlString($warningMessage . $infoMessage);
                    })
                    ->modalWidth('lg'),

                Action::make('renew')
                    ->label('Erneuern')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (AppToken $record) {
                        $record->renew();
                        Notification::make()
                            ->title('Token erneuert')
                            ->body("Token '{$record->name}' wurde um 2 Jahre verlängert.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Token erneuern')
                    ->modalSubheading('Möchten Sie die Gültigkeit dieses Tokens um 2 Jahre verlängern?'),

                Action::make('toggle_active')
                    ->label(fn (AppToken $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (AppToken $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (AppToken $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (AppToken $record) {
                        if ($record->is_active) {
                            $record->disable();
                            $message = "Token '{$record->name}' wurde deaktiviert.";
                        } else {
                            $record->enable();
                            $message = "Token '{$record->name}' wurde aktiviert.";
                        }
                        
                        Notification::make()
                            ->title('Token-Status geändert')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->disable();
                            Notification::make()
                                ->title('Tokens deaktiviert')
                                ->body(count($records) . ' Token(s) wurden deaktiviert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('enable')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->enable();
                            Notification::make()
                                ->title('Tokens aktiviert')
                                ->body(count($records) . ' Token(s) wurden aktiviert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
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
            'index' => Pages\ListAppTokens::route('/'),
            'create' => Pages\CreateAppToken::route('/create'),
            'edit' => Pages\EditAppToken::route('/{record}/edit'),
        ];
    }
}
