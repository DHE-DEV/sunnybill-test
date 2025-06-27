<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingResource\Pages;
use App\Models\CompanySetting;
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
                                            ->image()
                                            ->directory('company-logos')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                null,
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                            ])
                                            ->helperText('Empfohlene Formate: PNG, JPG, SVG. Wird anstatt des Textes "SunnyBill" angezeigt.'),
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
                Tables\Actions\EditAction::make(),
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
        // Nur eine Einstellung erlauben
        return CompanySetting::count() === 0;
    }
}
