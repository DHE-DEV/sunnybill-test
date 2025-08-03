<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingResource\Pages;
use App\Models\CompanySetting;
use App\Services\DocumentFormBuilder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanySettingResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationLabel = 'Firmeneinstellungen';
    
    protected static ?string $modelLabel = 'Firmeneinstellung';
    
    protected static ?string $pluralModelLabel = 'Firmeneinstellungen';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;
    
    protected static bool $isNavigationGroupCollapsed = true;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Einstellungen')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Firmeninformationen')
                            ->schema([
                                Forms\Components\Section::make('Grunddaten')
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Firmenname')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('company_legal_form')
                                            ->label('Rechtsform')
                                            ->placeholder('z.B. GmbH, AG, UG')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('company_address')
                                            ->label('Adresse')
                                            ->rows(2)
                                            ->maxLength(500),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('company_postal_code')
                                                    ->label('PLZ')
                                                    ->maxLength(10),
                                                Forms\Components\TextInput::make('company_city')
                                                    ->label('Stadt')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('company_country')
                                                    ->label('Land')
                                                    ->default('Deutschland')
                                                    ->maxLength(255),
                                            ]),
                                    ])->columns(2),
                                
                                Forms\Components\Section::make('Kontaktdaten')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('phone')
                                                    ->label('Telefon')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('fax')
                                                    ->label('Fax')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('email')
                                                    ->label('E-Mail')
                                                    ->email()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('website')
                                                    ->label('Website')
                                                    ->url()
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Rechtliche Informationen')
                            ->schema([
                                Forms\Components\Section::make('Steuerdaten')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('tax_number')
                                                    ->label('Steuernummer')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('vat_id')
                                                    ->label('USt-IdNr.')
                                                    ->maxLength(255),
                                            ]),
                                    ]),
                                
                                Forms\Components\Section::make('Handelsregister')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('commercial_register')
                                                    ->label('Registergericht')
                                                    ->placeholder('z.B. Amtsgericht München')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('commercial_register_number')
                                                    ->label('Handelsregisternummer')
                                                    ->placeholder('z.B. HRB 12345')
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\TextInput::make('management')
                                            ->label('Geschäftsführung')
                                            ->maxLength(255),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Bankdaten')
                            ->schema([
                                Forms\Components\Section::make('Bankverbindung')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Bank')
                                            ->maxLength(255),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('iban')
                                                    ->label('IBAN')
                                                    ->maxLength(34),
                                                Forms\Components\TextInput::make('bic')
                                                    ->label('BIC')
                                                    ->maxLength(11),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Logo & Design')
                            ->schema([
                                Forms\Components\Section::make('Logo-Upload')
                                    ->schema([
                                        Forms\Components\FileUpload::make('logo_path')
                                            ->label('Firmenlogo')
                                            ->required(false)
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'])
                                            ->maxSize(51200) // 50MB
                                            ->disk('public') // Lokaler öffentlicher Storage
                                            ->directory('logos') // Logos-Verzeichnis
                                            ->preserveFilenames(false)
                                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                                // Zeitstempel-basierte Benennung für Logos
                                                $pathInfo = pathinfo($file->getClientOriginalName());
                                                $name = $pathInfo['filename'] ?? 'logo';
                                                $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
                                                
                                                // Bereinige den ursprünglichen Namen
                                                $cleanName = preg_replace('/[^\w\-_.]/', '-', $name);
                                                $cleanName = preg_replace('/-+/', '-', $cleanName);
                                                $cleanName = trim($cleanName, '-');
                                                
                                                if (empty($cleanName)) {
                                                    $cleanName = 'logo';
                                                }
                                                
                                                // Generiere Zeitstempel
                                                $timestamp = now()->format('Y-m-d_H-i-s');
                                                
                                                return $cleanName . '_' . $timestamp . $extension;
                                            })
                                            ->helperText('Empfohlene Formate: PNG, JPG, SVG. Wird im lokalen Storage gespeichert und in PDFs verwendet.'),
                                    ]),
                                
                                Forms\Components\Section::make('Logo-Größe')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('logo_width')
                                                    ->label('Breite (px)')
                                                    ->numeric()
                                                    ->default(200)
                                                    ->minValue(50)
                                                    ->maxValue(800),
                                                Forms\Components\TextInput::make('logo_height')
                                                    ->label('Höhe (px)')
                                                    ->numeric()
                                                    ->default(60)
                                                    ->minValue(20)
                                                    ->maxValue(300),
                                            ]),
                                    ]),
                                
                                Forms\Components\Section::make('Logo-Abstände')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('logo_margin_top')
                                                    ->label('Oben (px)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(100),
                                                Forms\Components\TextInput::make('logo_margin_right')
                                                    ->label('Rechts (px)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(100),
                                                Forms\Components\TextInput::make('logo_margin_bottom')
                                                    ->label('Unten (px)')
                                                    ->numeric()
                                                    ->default(30)
                                                    ->minValue(0)
                                                    ->maxValue(100),
                                                Forms\Components\TextInput::make('logo_margin_left')
                                                    ->label('Links (px)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->maxValue(100),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Zahlungsbedingungen')
                            ->schema([
                                Forms\Components\Section::make('Standard-Zahlungsbedingungen')
                                    ->schema([
                                        Forms\Components\TextInput::make('default_payment_days')
                                            ->label('Zahlungsziel (Tage)')
                                            ->numeric()
                                            ->default(14)
                                            ->minValue(1)
                                            ->maxValue(365),
                                        Forms\Components\Textarea::make('payment_terms')
                                            ->label('Zahlungsbedingungen-Text')
                                            ->rows(3)
                                            ->placeholder('z.B. Zahlung innerhalb von 14 Tagen ohne Abzug.')
                                            ->maxLength(1000),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('PDF-Layout')
                            ->schema([
                                Forms\Components\Section::make('Seitenränder')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('pdf_margin_top')
                                                    ->label('Oben (cm)')
                                                    ->numeric()
                                                    ->step(0.1)
                                                    ->inputMode('decimal')
                                                    ->default(1.0)
                                                    ->minValue(0.5)
                                                    ->maxValue(5.0),
                                                Forms\Components\TextInput::make('pdf_margin_right')
                                                    ->label('Rechts (cm)')
                                                    ->numeric()
                                                    ->step(0.1)
                                                    ->inputMode('decimal')
                                                    ->default(2.0)
                                                    ->minValue(0.5)
                                                    ->maxValue(5.0),
                                                Forms\Components\TextInput::make('pdf_margin_bottom')
                                                    ->label('Unten (cm)')
                                                    ->numeric()
                                                    ->step(0.1)
                                                    ->inputMode('decimal')
                                                    ->default(2.5)
                                                    ->minValue(0.5)
                                                    ->maxValue(5.0),
                                                Forms\Components\TextInput::make('pdf_margin_left')
                                                    ->label('Links (cm)')
                                                    ->numeric()
                                                    ->step(0.1)
                                                    ->inputMode('decimal')
                                                    ->default(2.0)
                                                    ->minValue(0.5)
                                                    ->maxValue(5.0),
                                            ]),
                                    ])
                                    ->description('Seitenränder für PDF-Rechnungen'),
                           ]),

                       Forms\Components\Tabs\Tab::make('Preisformatierung')
                           ->schema([
                               Forms\Components\Section::make('Nachkommastellen')
                                   ->schema([
                                       Forms\Components\Grid::make(2)
                                           ->schema([
                                               Forms\Components\Select::make('article_price_decimal_places')
                                                   ->label('Artikelpreis Nachkommastellen')
                                                   ->options([
                                                       0 => '0 Nachkommastellen',
                                                       1 => '1 Nachkommastelle',
                                                       2 => '2 Nachkommastellen',
                                                       3 => '3 Nachkommastellen',
                                                       4 => '4 Nachkommastellen',
                                                       5 => '5 Nachkommastellen',
                                                       6 => '6 Nachkommastellen',
                                                   ])
                                                   ->default(2)
                                                   ->required()
                                                   ->helperText('Anzahl der Nachkommastellen für Artikelpreise (0-6)'),
                                               Forms\Components\Select::make('total_price_decimal_places')
                                                   ->label('Gesamtpreis Nachkommastellen')
                                                   ->options([
                                                       0 => '0 Nachkommastellen',
                                                       1 => '1 Nachkommastelle',
                                                       2 => '2 Nachkommastellen',
                                                       3 => '3 Nachkommastellen',
                                                       4 => '4 Nachkommastellen',
                                                       5 => '5 Nachkommastellen',
                                                       6 => '6 Nachkommastellen',
                                                   ])
                                                   ->default(2)
                                                   ->required()
                                                   ->helperText('Anzahl der Nachkommastellen für Gesamtpreise (0-6)'),
                                           ]),
                                   ])
                                   ->description('Konfiguration der Nachkommastellen für Preisanzeigen in der gesamten Anwendung'),
                           ]),

                       Forms\Components\Tabs\Tab::make('Nummernformate')
                           ->schema([
                               Forms\Components\Section::make('Präfixe für Nummernformate')
                                   ->schema([
                                       Forms\Components\Grid::make(2)
                                           ->schema([
                                               Forms\Components\TextInput::make('customer_number_prefix')
                                                   ->label('Kundennummer-Präfix')
                                                   ->placeholder('z.B. KD, KUNDE')
                                                   ->maxLength(10)
                                                   ->reactive()
                                                   ->helperText('Optional: Präfix vor der Kundennummer (z.B. KD-0001)'),
                                               Forms\Components\TextInput::make('supplier_number_prefix')
                                                   ->label('Lieferantennummer-Präfix')
                                                   ->placeholder('z.B. LF, LIEFERANT')
                                                   ->maxLength(10)
                                                   ->reactive()
                                                   ->helperText('Optional: Präfix vor der Lieferantennummer (z.B. LF-0001)'),
                                           ]),
                                       Forms\Components\Grid::make(2)
                                           ->schema([
                                               Forms\Components\TextInput::make('invoice_number_prefix')
                                                   ->label('Rechnungsnummer-Präfix')
                                                   ->placeholder('z.B. RE, RECHNUNG')
                                                   ->maxLength(10)
                                                   ->reactive()
                                                   ->helperText('Optional: Präfix vor der Rechnungsnummer'),
                                               Forms\Components\Toggle::make('invoice_number_include_year')
                                                   ->label('Jahr in Rechnungsnummer')
                                                   ->helperText('Fügt das aktuelle Jahr zur Rechnungsnummer hinzu (z.B. RE-2025-0001)')
                                                   ->default(false)
                                                   ->reactive(),
                                           ]),
                                       Forms\Components\Grid::make(2)
                                           ->schema([
                                               Forms\Components\TextInput::make('solar_plant_number_prefix')
                                                   ->label('Solaranlagen-Präfix')
                                                   ->placeholder('z.B. SA, SOLAR')
                                                   ->maxLength(10)
                                                   ->reactive()
                                                   ->helperText('Optional: Präfix vor der Solaranlagen-Nummer (z.B. SA-0001)'),
                                               Forms\Components\TextInput::make('project_number_prefix')
                                                   ->label('Projekt-Präfix')
                                                   ->placeholder('z.B. PRJ, PROJEKT')
                                                   ->maxLength(10)
                                                   ->reactive()
                                                   ->helperText('Optional: Präfix vor der Projekt-Nummer (z.B. PRJ-0001)'),
                                           ]),
                                   ])
                                   ->description('Konfigurieren Sie Präfixe für automatisch generierte Nummern. Alle Teile werden durch Bindestriche (-) getrennt.'),
                               
                               Forms\Components\Section::make('Beispiele')
                                   ->schema([
                                       Forms\Components\Placeholder::make('examples')
                                           ->label('')
                                           ->content(function ($get) {
                                               $customerPrefix = $get('customer_number_prefix') ?: '[Kein Präfix]';
                                               $supplierPrefix = $get('supplier_number_prefix') ?: '[Kein Präfix]';
                                               $invoicePrefix = $get('invoice_number_prefix') ?: '[Kein Präfix]';
                                               $solarPlantPrefix = $get('solar_plant_number_prefix') ?: '[Kein Präfix]';
                                               $projectPrefix = $get('project_number_prefix') ?: '[Kein Präfix]';
                                               $includeYear = $get('invoice_number_include_year');
                                               
                                               $customerExample = $customerPrefix === '[Kein Präfix]' ? '0001' : $customerPrefix . '-0001';
                                               $supplierExample = $supplierPrefix === '[Kein Präfix]' ? '0001' : $supplierPrefix . '-0001';
                                               $solarPlantExample = $solarPlantPrefix === '[Kein Präfix]' ? '0001' : $solarPlantPrefix . '-0001';
                                               $projectExample = $projectPrefix === '[Kein Präfix]' ? '0001' : $projectPrefix . '-0001';
                                               
                                               $invoiceExample = '';
                                               if ($invoicePrefix !== '[Kein Präfix]') {
                                                   $invoiceExample .= $invoicePrefix . '-';
                                               }
                                               if ($includeYear) {
                                                   $invoiceExample .= date('Y') . '-';
                                               }
                                               $invoiceExample .= '0001';
                                               
                                               return new \Illuminate\Support\HtmlString("
                                                   <div class='space-y-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border'>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Kundennummer:</span>
                                                           <span class='font-mono text-sm bg-white dark:bg-gray-700 px-2 py-1 rounded border'>{$customerExample}</span>
                                                       </div>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Lieferantennummer:</span>
                                                           <span class='font-mono text-sm bg-white dark:bg-gray-700 px-2 py-1 rounded border'>{$supplierExample}</span>
                                                       </div>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Rechnungsnummer:</span>
                                                           <span class='font-mono text-sm bg-white dark:bg-gray-700 px-2 py-1 rounded border'>{$invoiceExample}</span>
                                                       </div>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Solaranlagen-Nr.:</span>
                                                           <span class='font-mono text-sm bg-white dark:bg-gray-700 px-2 py-1 rounded border'>{$solarPlantExample}</span>
                                                       </div>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Projekt-Nr.:</span>
                                                           <span class='font-mono text-sm bg-white dark:bg-gray-700 px-2 py-1 rounded border'>{$projectExample}</span>
                                                       </div>
                                                   </div>
                                               ");
                                           })
                                           ->reactive(),
                                   ])
                                   ->collapsible(),
                           ]),

                       Forms\Components\Tabs\Tab::make('Portal-Einstellungen')
                           ->schema([
                               Forms\Components\Section::make('Portal-Konfiguration')
                                   ->schema([
                                       Forms\Components\Toggle::make('portal_enabled')
                                           ->label('Portal aktiviert')
                                           ->helperText('Aktiviert oder deaktiviert das Kundenportal')
                                           ->default(true)
                                           ->reactive(),
                                       Forms\Components\TextInput::make('portal_name')
                                           ->label('Portal-Name')
                                           ->placeholder('z.B. Kundenportal, Mein SunnyBill')
                                           ->maxLength(255)
                                           ->helperText('Name des Portals, der in E-Mails und auf der Login-Seite angezeigt wird'),
                                       Forms\Components\TextInput::make('portal_url')
                                           ->label('Portal-URL')
                                           ->url()
                                           ->placeholder('https://portal.ihrefirma.de')
                                           ->maxLength(255)
                                           ->helperText('Vollständige URL zum Kundenportal (wird in E-Mails verwendet)')
                                           ->required(fn ($get) => $get('portal_enabled')),
                                       Forms\Components\Textarea::make('portal_description')
                                           ->label('Portal-Beschreibung')
                                           ->rows(3)
                                           ->placeholder('Beschreibung des Portals für E-Mails und Dokumentation')
                                           ->maxLength(1000)
                                           ->helperText('Optional: Beschreibung des Portals für interne Zwecke'),
                                   ])
                                   ->description('Konfigurieren Sie die Einstellungen für das Kundenportal. Die Portal-URL wird in E-Mail-Benachrichtigungen verwendet.'),
                               
                               Forms\Components\Section::make('Vorschau')
                                   ->schema([
                                       Forms\Components\Placeholder::make('portal_preview')
                                           ->label('')
                                           ->content(function ($get) {
                                               $enabled = $get('portal_enabled');
                                               $name = $get('portal_name') ?: 'SunnyBill Portal';
                                               $url = $get('portal_url') ?: 'https://portal.ihrefirma.de';
                                               $description = $get('portal_description') ?: 'Keine Beschreibung';
                                               
                                               $status = $enabled ?
                                                   '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Aktiviert</span>' :
                                                   '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">Deaktiviert</span>';
                                               
                                               return new \Illuminate\Support\HtmlString("
                                                   <div class='space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border'>
                                                       <div class='flex justify-between items-center'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Status:</span>
                                                           {$status}
                                                       </div>
                                                       <div class='flex justify-between items-start'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Portal-Name:</span>
                                                           <span class='text-sm text-gray-600 dark:text-gray-400 text-right'>{$name}</span>
                                                       </div>
                                                       <div class='flex justify-between items-start'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Portal-URL:</span>
                                                           <span class='text-sm text-blue-600 dark:text-blue-400 text-right break-all'>{$url}</span>
                                                       </div>
                                                       <div class='flex justify-between items-start'>
                                                           <span class='font-medium text-gray-700 dark:text-gray-300'>Beschreibung:</span>
                                                           <span class='text-sm text-gray-600 dark:text-gray-400 text-right max-w-xs'>{$description}</span>
                                                       </div>
                                                       <div class='mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800'>
                                                           <p class='text-sm text-blue-800 dark:text-blue-200'>
                                                               <strong>Hinweis:</strong> Diese URL wird in E-Mail-Benachrichtigungen für die Benutzeraktivierung und E-Mail-Bestätigung verwendet.
                                                           </p>
                                                       </div>
                                                   </div>
                                               ");
                                           })
                                           ->reactive(),
                                   ])
                                   ->collapsible(),
                           ]),

                        Forms\Components\Tabs\Tab::make('Lexware-Synchronisation')
                            ->schema([
                                Forms\Components\Section::make('API-Konfiguration')
                                    ->schema([
                                        Forms\Components\Toggle::make('lexware_sync_enabled')
                                            ->label('Lexware-Synchronisation aktiviert')
                                            ->helperText('Aktiviert oder deaktiviert die automatische Synchronisation mit Lexware/Lexoffice')
                                            ->default(false)
                                            ->reactive(),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('lexware_api_url')
                                                    ->label('API-URL')
                                                    ->url()
                                                    ->default('https://api.lexoffice.io/v1')
                                                    ->placeholder('https://api.lexoffice.io/v1')
                                                    ->maxLength(255)
                                                    ->helperText('Standard: https://api.lexoffice.io/v1')
                                                    ->visible(fn ($get) => $get('lexware_sync_enabled')),
                                                
                                                Forms\Components\TextInput::make('lexware_organization_id')
                                                    ->label('Organisation-ID')
                                                    ->placeholder('z.B. 801ccedc-d81c-43a5-b0d4-031ec6909bcb')
                                                    ->maxLength(255)
                                                    ->helperText('Die Organisation-ID aus Ihrem Lexware-Account')
                                                    ->required(fn ($get) => $get('lexware_sync_enabled'))
                                                    ->visible(fn ($get) => $get('lexware_sync_enabled')),
                                            ]),
                                        
                                        Forms\Components\TextInput::make('lexware_api_key')
                                            ->label('API-Schlüssel')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Ihr Lexware API-Schlüssel')
                                            ->maxLength(255)
                                            ->helperText('Der API-Schlüssel für den Zugriff auf die Lexware-API')
                                            ->required(fn ($get) => $get('lexware_sync_enabled'))
                                            ->visible(fn ($get) => $get('lexware_sync_enabled')),
                                    ])
                                    ->description('Grundlegende API-Einstellungen für die Verbindung zu Lexware/Lexoffice'),
                                
                                Forms\Components\Section::make('Synchronisations-Optionen')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Toggle::make('lexware_auto_sync_customers')
                                                    ->label('Automatische Kunden-Synchronisation')
                                                    ->helperText('Synchronisiert Kunden automatisch bei Änderungen')
                                                    ->default(true),
                                                
                                                Forms\Components\Toggle::make('lexware_auto_sync_addresses')
                                                    ->label('Automatische Adress-Synchronisation')
                                                    ->helperText('Synchronisiert Adressen automatisch bei Änderungen')
                                                    ->default(true),
                                                
                                                Forms\Components\Toggle::make('lexware_import_customer_numbers')
                                                    ->label('Kundennummern importieren')
                                                    ->helperText('Importiert Kundennummern aus Lexware')
                                                    ->default(true),
                                            ]),
                                    ])
                                    ->visible(fn ($get) => $get('lexware_sync_enabled'))
                                    ->description('Konfigurieren Sie, welche Daten automatisch synchronisiert werden sollen'),
                                
                                Forms\Components\Section::make('Debug & Logging')
                                    ->schema([
                                        Forms\Components\Toggle::make('lexware_debug_logging')
                                            ->label('Debug-Logging aktiviert')
                                            ->helperText('Aktiviert detailliertes Logging für Debugging-Zwecke')
                                            ->default(false),
                                    ])
                                    ->visible(fn ($get) => $get('lexware_sync_enabled'))
                                    ->description('Erweiterte Einstellungen für Debugging und Fehlerbehebung'),
                                
                                Forms\Components\Section::make('Status & Informationen')
                                    ->schema([
                                        Forms\Components\Placeholder::make('lexware_status')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Speichern Sie die Einstellungen, um den Status zu sehen.</p>');
                                                }
                                                
                                                $status = $record->getLexwareConfigStatus();
                                                
                                                $statusColor = $status['is_valid'] ? 'green' : 'red';
                                                $statusText = $status['is_valid'] ? 'Konfiguration vollständig' : 'Konfiguration unvollständig';
                                                $statusIcon = $status['is_valid'] ? '✅' : '❌';
                                                
                                                $lastSync = $status['last_sync'] ? $status['last_sync'] : 'Noch nie';
                                                $lastError = $status['last_error'] ? $status['last_error'] : 'Keine Fehler';
                                                
                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='space-y-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border'>
                                                        <div class='flex justify-between items-center'>
                                                            <span class='font-medium text-gray-700 dark:text-gray-300'>Konfigurationsstatus:</span>
                                                            <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-800 dark:bg-{$statusColor}-800 dark:text-{$statusColor}-100'>
                                                                {$statusIcon} {$statusText}
                                                            </span>
                                                        </div>
                                                        <div class='flex justify-between items-center'>
                                                            <span class='font-medium text-gray-700 dark:text-gray-300'>API-Schlüssel:</span>
                                                            <span class='text-sm'>" . ($status['api_key_set'] ? '✅ Gesetzt' : '❌ Nicht gesetzt') . "</span>
                                                        </div>
                                                        <div class='flex justify-between items-center'>
                                                            <span class='font-medium text-gray-700 dark:text-gray-300'>Organisation-ID:</span>
                                                            <span class='text-sm'>" . ($status['organization_id_set'] ? '✅ Gesetzt' : '❌ Nicht gesetzt') . "</span>
                                                        </div>
                                                        <div class='flex justify-between items-center'>
                                                            <span class='font-medium text-gray-700 dark:text-gray-300'>Letzte Synchronisation:</span>
                                                            <span class='text-sm text-gray-600 dark:text-gray-400'>{$lastSync}</span>
                                                        </div>
                                                        <div class='flex justify-between items-start'>
                                                            <span class='font-medium text-gray-700 dark:text-gray-300'>Letzter Fehler:</span>
                                                            <span class='text-sm text-gray-600 dark:text-gray-400 text-right max-w-xs break-words'>{$lastError}</span>
                                                        </div>
                                                    </div>
                                                ");
                                            }),
                                    ])
                                    ->visible(fn ($get) => $get('lexware_sync_enabled'))
                                    ->collapsible(),
                                
                                Forms\Components\Section::make('Hilfe & Dokumentation')
                                    ->schema([
                                        Forms\Components\Placeholder::make('lexware_help')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString("
                                                <div class='space-y-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800'>
                                                    <h4 class='font-semibold text-blue-800 dark:text-blue-200'>So richten Sie die Lexware-Synchronisation ein:</h4>
                                                    <ol class='list-decimal list-inside space-y-2 text-sm text-blue-700 dark:text-blue-300'>
                                                        <li>Loggen Sie sich in Ihr Lexoffice-Konto ein</li>
                                                        <li>Gehen Sie zu den API-Einstellungen</li>
                                                        <li>Erstellen Sie einen neuen API-Schlüssel</li>
                                                        <li>Kopieren Sie die Organisation-ID aus Ihrem Account</li>
                                                        <li>Tragen Sie beide Werte in die Felder oben ein</li>
                                                        <li>Aktivieren Sie die Synchronisation</li>
                                                    </ol>
                                                    <div class='mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800'>
                                                        <p class='text-sm text-yellow-800 dark:text-yellow-200'>
                                                            <strong>Wichtig:</strong> Der API-Schlüssel wird verschlüsselt gespeichert und ist nur für autorisierte Benutzer sichtbar.
                                                        </p>
                                                    </div>
                                                </div>
                                            ")),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Gmail-Integration')
                            ->schema([
                                Forms\Components\Section::make('OAuth2-Status')
                                    ->schema([
                                        Forms\Components\Placeholder::make('gmail_oauth_status')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500">Speichern Sie die Einstellungen, um den Status zu sehen.</p>');
                                                }
                                                
                                                $enabled = $record->gmail_enabled;
                                                $clientId = $record->gmail_client_id;
                                                $clientSecret = $record->gmail_client_secret;
                                                $accessToken = $record->gmail_access_token;
                                                $refreshToken = $record->gmail_refresh_token;
                                                $emailAddress = $record->gmail_email_address;
                                                $tokenExpiresAt = $record->gmail_token_expires_at;
                                                
                                                $statusItems = [
                                                    'Gmail aktiviert' => $enabled ? '✅ Ja' : '❌ Nein',
                                                    'Client ID' => $clientId ? '✅ Vorhanden (' . substr($clientId, 0, 20) . '...)' : '❌ Fehlt',
                                                    'Client Secret' => $clientSecret ? '✅ Vorhanden (' . substr($clientSecret, 0, 10) . '...)' : '❌ Fehlt',
                                                    'Access Token' => $accessToken ? '✅ Vorhanden (' . substr($accessToken, 0, 20) . '...)' : '❌ Fehlt',
                                                    'Refresh Token' => $refreshToken ? '✅ Vorhanden (' . substr($refreshToken, 0, 20) . '...)' : '❌ Fehlt',
                                                    'E-Mail-Adresse' => $emailAddress ? '✅ ' . $emailAddress : '❌ Nicht gesetzt',
                                                ];
                                                
                                                if ($tokenExpiresAt) {
                                                    $expiresAt = \Carbon\Carbon::parse($tokenExpiresAt);
                                                    $isExpired = $expiresAt->isPast();
                                                    $statusItems['Token läuft ab'] = $expiresAt->format('d.m.Y H:i:s') . ($isExpired ? ' ❌ (ABGELAUFEN)' : ' ✅ (Gültig)');
                                                } else {
                                                    $statusItems['Token Ablauf'] = '❌ Nicht gesetzt';
                                                }
                                                
                                                $html = '<div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border">';
                                                foreach ($statusItems as $label => $value) {
                                                    $html .= '<div class="flex justify-between items-center">';
                                                    $html .= '<span class="font-medium text-gray-700 dark:text-gray-300">' . $label . ':</span>';
                                                    $html .= '<span class="text-sm">' . $value . '</span>';
                                                    $html .= '</div>';
                                                }
                                                
                                                // Fehlende Konfiguration identifizieren
                                                $missing = [];
                                                if (!$enabled) $missing[] = 'Gmail aktivieren';
                                                if (!$clientId) $missing[] = 'Client ID';
                                                if (!$clientSecret) $missing[] = 'Client Secret';
                                                if (!$accessToken) $missing[] = 'Access Token';
                                                if (!$refreshToken) $missing[] = 'Refresh Token';
                                                if (!$emailAddress) $missing[] = 'E-Mail-Adresse';
                                                
                                                if (count($missing) > 0) {
                                                    $html .= '<div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">';
                                                    $html .= '<p class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Fehlende Konfiguration:</p>';
                                                    $html .= '<ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">';
                                                    foreach ($missing as $item) {
                                                        $html .= '<li>' . $item . '</li>';
                                                    }
                                                    $html .= '</ul>';
                                                    
                                                    if (!$accessToken || !$refreshToken) {
                                                        $html .= '<div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">';
                                                        $html .= '<p class="text-sm text-blue-800 dark:text-blue-200">';
                                                        $html .= '<strong>Nächster Schritt:</strong> Klicken Sie auf "Gmail autorisieren" um OAuth2-Tokens zu erhalten.';
                                                        $html .= '</p>';
                                                        $html .= '</div>';
                                                    }
                                                    
                                                    $html .= '</div>';
                                                } else {
                                                    $html .= '<div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">';
                                                    $html .= '<p class="text-sm text-green-800 dark:text-green-200">';
                                                    $html .= '<strong>✅ Konfiguration vollständig!</strong> Gmail-Integration ist bereit.';
                                                    $html .= '</p>';
                                                    $html .= '</div>';
                                                }
                                                
                                                $html .= '</div>';
                                                
                                                return new \Illuminate\Support\HtmlString($html);
                                            })
                                            ->reactive(),
                                    ])
                                    ->description('Live-Status der Gmail OAuth2-Konfiguration')
                                    ->collapsible(),
                                
                                Forms\Components\Section::make('OAuth2-Konfiguration')
                                    ->schema([
                                        Forms\Components\Toggle::make('gmail_enabled')
                                            ->label('Gmail-Integration aktiviert')
                                            ->helperText('Aktiviert oder deaktiviert die Gmail-Integration')
                                            ->default(false)
                                            ->reactive(),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('gmail_client_id')
                                                    ->label('Client ID')
                                                    ->placeholder('Ihre Gmail OAuth2 Client ID')
                                                    ->maxLength(255)
                                                    ->helperText('Die Client ID aus der Google Cloud Console')
                                                    ->required(fn ($get) => $get('gmail_enabled'))
                                                    ->visible(fn ($get) => $get('gmail_enabled')),
                                                
                                                Forms\Components\TextInput::make('gmail_client_secret')
                                                    ->label('Client Secret')
                                                    ->password()
                                                    ->revealable()
                                                    ->placeholder('Ihr Gmail OAuth2 Client Secret')
                                                    ->maxLength(255)
                                                    ->helperText('Das Client Secret aus der Google Cloud Console')
                                                    ->required(fn ($get) => $get('gmail_enabled'))
                                                    ->visible(fn ($get) => $get('gmail_enabled')),
                                            ]),
                                        
                        Forms\Components\TextInput::make('gmail_email_address')
                            ->label('Verbundene E-Mail-Adresse')
                            ->email()
                            ->disabled()
                            ->placeholder('Wird nach der Autorisierung angezeigt')
                            ->helperText('Die E-Mail-Adresse des verbundenen Gmail-Kontos')
                            ->visible(fn ($get) => $get('gmail_enabled')),
                        
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('authorize_gmail')
                                ->label('Gmail autorisieren')
                                ->icon('heroicon-o-key')
                                ->color('primary')
                                ->action(function ($record) {
                                    if (!$record) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Fehler')
                                            ->body('Bitte speichern Sie zuerst die Einstellungen.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    try {
                                        $gmailService = new \App\Services\GmailService();
                                        $redirectUri = url('/admin/gmail/oauth/callback');
                                        $authUrl = $gmailService->getAuthorizationUrl($redirectUri);
                                        
                                        // Öffne Authorization URL in neuem Tab
                                        return redirect()->away($authUrl);
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Autorisierung fehlgeschlagen')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    return $record->isGmailEnabled() && 
                                           $record->getGmailClientId() && 
                                           $record->getGmailClientSecret() && 
                                           !$record->getGmailRefreshToken();
                                }),
                            
                            Forms\Components\Actions\Action::make('test_gmail_connection')
                                ->label('Verbindung testen')
                                ->icon('heroicon-o-wifi')
                                ->color('secondary')
                                ->action(function ($record) {
                                    if (!$record) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Fehler')
                                            ->body('Bitte speichern Sie zuerst die Einstellungen.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    try {
                                        $gmailService = new \App\Services\GmailService();
                                        $result = $gmailService->testConnection();
                                        
                                        if ($result['success']) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Verbindung erfolgreich')
                                                ->body("Verbunden mit: {$result['email']}")
                                                ->success()
                                                ->send();
                                        } else {
                                            throw new \Exception($result['error']);
                                        }
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Verbindung fehlgeschlagen')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    return $record->isGmailEnabled() && 
                                           $record->getGmailRefreshToken();
                                }),
                            
                            Forms\Components\Actions\Action::make('send_test_email')
                                ->label('Testmail senden')
                                ->icon('heroicon-o-envelope')
                                ->color('success')
                                ->action(function ($record) {
                                    if (!$record) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Fehler')
                                            ->body('Bitte speichern Sie zuerst die Einstellungen.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    try {
                                        $gmailService = new \App\Services\GmailService();
                                        
                                        // Test-E-Mail Daten
                                        $to = 'dh@dhe.de';
                                        $subject = 'SunnyBill Gmail-Integration Test';
                                        $body = "Hallo,\n\ndies ist eine Test-E-Mail von der SunnyBill Gmail-Integration.\n\nGesendet am: " . now()->format('d.m.Y H:i:s') . "\n\nMit freundlichen Grüßen\nIhr SunnyBill Team";
                                        
                                        $result = $gmailService->sendEmail($to, $subject, $body);
                                        
                                        if ($result['success']) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Testmail gesendet')
                                                ->body("Test-E-Mail wurde erfolgreich an {$to} gesendet.")
                                                ->success()
                                                ->send();
                                        } else {
                                            throw new \Exception($result['error']);
                                        }
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Testmail fehlgeschlagen')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    return $record->isGmailEnabled() &&
                                           $record->getGmailRefreshToken();
                                }),
                            
                            Forms\Components\Actions\Action::make('revoke_gmail_access')
                                ->label('Zugriff widerrufen')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Gmail-Zugriff widerrufen')
                                ->modalDescription('Sind Sie sicher, dass Sie den Gmail-Zugriff widerrufen möchten? Dies entfernt alle gespeicherten Tokens.')
                                ->action(function ($record) {
                                    if (!$record) return;
                                    
                                    try {
                                        $record->clearGmailTokens();
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->title('Zugriff widerrufen')
                                            ->body('Der Gmail-Zugriff wurde erfolgreich widerrufen.')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Fehler beim Widerrufen')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(function ($record) {
                                    if (!$record) return false;
                                    return $record->isGmailEnabled() &&
                                           $record->getGmailRefreshToken();
                                }),
                        ])
                        ->visible(fn ($get) => $get('gmail_enabled')),
                                    ])
                                    ->description('OAuth2-Einstellungen für die Verbindung zu Gmail'),
                                
                                Forms\Components\Section::make('Synchronisations-Einstellungen')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Toggle::make('gmail_auto_sync')
                                                    ->label('Automatische Synchronisation')
                                                    ->helperText('Synchronisiert E-Mails automatisch')
                                                    ->default(true),
                                                
                                                Forms\Components\TextInput::make('gmail_sync_interval')
                                                    ->label('Sync-Intervall (Minuten)')
                                                    ->numeric()
                                                    ->default(5)
                                                    ->minValue(1)
                                                    ->maxValue(60)
                                                    ->helperText('Intervall für automatische Synchronisation'),
                                                
                                                Forms\Components\TextInput::make('gmail_max_results')
                                                    ->label('Max. E-Mails pro Sync')
                                                    ->numeric()
                                                    ->default(100)
                                                    ->minValue(10)
                                                    ->maxValue(500)
                                                    ->helperText('Maximale Anzahl E-Mails pro Synchronisation'),
                                            ]),
                                    ])
                                    ->visible(fn ($get) => $get('gmail_enabled'))
                                    ->description('Konfigurieren Sie die automatische E-Mail-Synchronisation'),
                                
                                Forms\Components\Section::make('Anhang-Einstellungen')
                                    ->schema([
                                        Forms\Components\Toggle::make('gmail_download_attachments')
                                            ->label('Anhänge herunterladen')
                                            ->helperText('Lädt E-Mail-Anhänge automatisch herunter')
                                            ->default(true),
                                        
                                        Forms\Components\TextInput::make('gmail_attachment_path')
                                            ->label('Anhang-Pfad')
                                            ->default('gmail-attachments')
                                            ->placeholder('gmail-attachments')
                                            ->maxLength(255)
                                            ->helperText('Verzeichnis für heruntergeladene Anhänge'),
                                    ])
                                    ->visible(fn ($get) => $get('gmail_enabled'))
                                    ->description('Einstellungen für den Download von E-Mail-Anhängen'),
                                
                                Forms\Components\Section::make('E-Mail-Verarbeitung')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('gmail_mark_as_read')
                                                    ->label('Als gelesen markieren')
                                                    ->helperText('Markiert verarbeitete E-Mails als gelesen')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('gmail_archive_processed')
                                                    ->label('Verarbeitete E-Mails archivieren')
                                                    ->helperText('Archiviert verarbeitete E-Mails automatisch')
                                                    ->default(false),
                                            ]),
                                        
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('gmail_filter_inbox')
                                                    ->label('INBOX-Filter aktiviert')
                                                    ->helperText('Filtert E-Mails mit INBOX-Label heraus')
                                                    ->default(false),
                                                
                                                Forms\Components\Toggle::make('gmail_logging_enabled')
                                                    ->label('E-Mail Logging aktiviert')
                                                    ->helperText('Speichert detaillierte E-Mail-Logs in der Datenbank')
                                                    ->default(false),
                                            ]),
                                        
                                        Forms\Components\TextInput::make('gmail_processed_label')
                                            ->label('Label für verarbeitete E-Mails')
                                            ->default('Processed')
                                            ->placeholder('Processed')
                                            ->maxLength(255)
                                            ->helperText('Label das verarbeiteten E-Mails hinzugefügt wird'),
                                    ])
                                    ->visible(fn ($get) => $get('gmail_enabled'))
                                    ->description('Konfigurieren Sie die automatische Verarbeitung von E-Mails'),
                            ]),

                   ])
                   ->columnSpanFull(),
           ])
           ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firmenname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon'),
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->size(40),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Zuletzt geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanySettings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        // Nur eine Einstellung erlauben und Zugriffskontrolle
        return CompanySetting::count() === 0 &&
               (auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false);
    }

    // Zugriffskontrolle für System-Ressourcen (Administrator + Superadmin Teams)
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }
}
