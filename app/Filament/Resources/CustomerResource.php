<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Document;
use App\Services\LexofficeService;
use App\Services\DocumentUploadConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Kunden';
    
    protected static ?string $modelLabel = 'Kunde';
    
    protected static ?string $pluralModelLabel = 'Kunden';

    protected static ?string $navigationGroup = 'Kunden';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kundendaten')
                    ->schema([
                        Forms\Components\Select::make('customer_type')
                            ->label('Kundentyp')
                            ->options([
                                'business' => 'Firmenkunde',
                                'private' => 'Privatkunde',
                            ])
                            ->default('business')
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('ranking')
                            ->label('Ranking')
                            ->options([
                                'A' => 'A Kunde',
                                'B' => 'B Kunde',
                                'C' => 'C Kunde',
                                'D' => 'D Kunde',
                                'E' => 'E Kunde',
                            ])
                            ->placeholder('Ranking auswählen')
                            ->helperText('Klassifizierung der Kundenwichtigkeit')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('customer_number')
                            ->label('Kundennummer')
                            ->maxLength(255)
                            ->placeholder('Wird automatisch generiert')
                            ->helperText('Leer lassen für automatische Generierung'),
                        Forms\Components\TextInput::make('name')
                            ->label(fn (Forms\Get $get) => $get('customer_type') === 'private' ? 'Name' : 'Firmenname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firmenname')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Ansprechpartner')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                        Forms\Components\TextInput::make('department')
                            ->label('Abteilung')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('fax')
                            ->label('Fax')
                            ->tel()
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),
                    ])->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\TextInput::make('street')
                            ->label('Straße & Hausnummer')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line_2')
                            ->label('Adresszusatz')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('PLZ')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('city')
                            ->label('Stadt')
                            ->maxLength(255),
                        Forms\Components\Select::make('country')
                            ->label('Land')
                            ->options([
                                'Deutschland' => 'Deutschland',
                                'Österreich' => 'Österreich',
                                'Schweiz' => 'Schweiz',
                                'Frankreich' => 'Frankreich',
                                'Italien' => 'Italien',
                                'Niederlande' => 'Niederlande',
                                'Belgien' => 'Belgien',
                                'Luxemburg' => 'Luxemburg',
                                'Dänemark' => 'Dänemark',
                                'Schweden' => 'Schweden',
                                'Norwegen' => 'Norwegen',
                                'Finnland' => 'Finnland',
                                'Polen' => 'Polen',
                                'Tschechien' => 'Tschechien',
                                'Slowakei' => 'Slowakei',
                                'Ungarn' => 'Ungarn',
                                'Slowenien' => 'Slowenien',
                                'Kroatien' => 'Kroatien',
                                'Spanien' => 'Spanien',
                                'Portugal' => 'Portugal',
                                'Vereinigtes Königreich' => 'Vereinigtes Königreich',
                                'Irland' => 'Irland',
                                'Vereinigte Staaten' => 'Vereinigte Staaten',
                                'Kanada' => 'Kanada',
                                'Australien' => 'Australien',
                                'Neuseeland' => 'Neuseeland',
                                'Japan' => 'Japan',
                                'Südkorea' => 'Südkorea',
                                'China' => 'China',
                                'Indien' => 'Indien',
                                'Brasilien' => 'Brasilien',
                                'Mexiko' => 'Mexiko',
                                'Argentinien' => 'Argentinien',
                                'Chile' => 'Chile',
                                'Südafrika' => 'Südafrika',
                                'Ägypten' => 'Ägypten',
                                'Israel' => 'Israel',
                                'Türkei' => 'Türkei',
                                'Russland' => 'Russland',
                                'Ukraine' => 'Ukraine',
                                'Weißrussland' => 'Weißrussland',
                                'Serbien' => 'Serbien',
                                'Bosnien und Herzegowina' => 'Bosnien und Herzegowina',
                                'Montenegro' => 'Montenegro',
                                'Nordmazedonien' => 'Nordmazedonien',
                                'Albanien' => 'Albanien',
                                'Bulgarien' => 'Bulgarien',
                                'Rumänien' => 'Rumänien',
                                'Moldau' => 'Moldau',
                                'Litauen' => 'Litauen',
                                'Lettland' => 'Lettland',
                                'Estland' => 'Estland',
                                'Griechenland' => 'Griechenland',
                                'Zypern' => 'Zypern',
                                'Malta' => 'Malta',
                                'Island' => 'Island',
                            ])
                            ->default('Deutschland')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Automatisch Ländercode setzen basierend auf Land
                                $countryCodes = [
                                    'Deutschland' => 'DE',
                                    'Österreich' => 'AT',
                                    'Schweiz' => 'CH',
                                    'Frankreich' => 'FR',
                                    'Italien' => 'IT',
                                    'Niederlande' => 'NL',
                                    'Belgien' => 'BE',
                                    'Luxemburg' => 'LU',
                                    'Dänemark' => 'DK',
                                    'Schweden' => 'SE',
                                    'Norwegen' => 'NO',
                                    'Finnland' => 'FI',
                                    'Polen' => 'PL',
                                    'Tschechien' => 'CZ',
                                    'Slowakei' => 'SK',
                                    'Ungarn' => 'HU',
                                    'Slowenien' => 'SI',
                                    'Kroatien' => 'HR',
                                    'Spanien' => 'ES',
                                    'Portugal' => 'PT',
                                    'Vereinigtes Königreich' => 'GB',
                                    'Irland' => 'IE',
                                    'Vereinigte Staaten' => 'US',
                                    'Kanada' => 'CA',
                                    'Australien' => 'AU',
                                    'Neuseeland' => 'NZ',
                                    'Japan' => 'JP',
                                    'Südkorea' => 'KR',
                                    'China' => 'CN',
                                    'Indien' => 'IN',
                                    'Brasilien' => 'BR',
                                    'Mexiko' => 'MX',
                                    'Argentinien' => 'AR',
                                    'Chile' => 'CL',
                                    'Südafrika' => 'ZA',
                                    'Ägypten' => 'EG',
                                    'Israel' => 'IL',
                                    'Türkei' => 'TR',
                                    'Russland' => 'RU',
                                    'Ukraine' => 'UA',
                                    'Weißrussland' => 'BY',
                                    'Serbien' => 'RS',
                                    'Bosnien und Herzegowina' => 'BA',
                                    'Montenegro' => 'ME',
                                    'Nordmazedonien' => 'MK',
                                    'Albanien' => 'AL',
                                    'Bulgarien' => 'BG',
                                    'Rumänien' => 'RO',
                                    'Moldau' => 'MD',
                                    'Litauen' => 'LT',
                                    'Lettland' => 'LV',
                                    'Estland' => 'EE',
                                    'Griechenland' => 'GR',
                                    'Zypern' => 'CY',
                                    'Malta' => 'MT',
                                    'Island' => 'IS',
                                ];
                                
                                $set('country_code', $countryCodes[$state] ?? 'DE');
                                
                                // Bundesland zurücksetzen wenn nicht Deutschland
                                if ($state !== 'Deutschland') {
                                    $set('state', null);
                                }
                            }),
                        Forms\Components\Select::make('state')
                            ->label('Bundesland/Region')
                            ->options(function (Forms\Get $get) {
                                if ($get('country') === 'Deutschland') {
                                    return [
                                        'Baden-Württemberg' => 'Baden-Württemberg',
                                        'Bayern' => 'Bayern',
                                        'Berlin' => 'Berlin',
                                        'Brandenburg' => 'Brandenburg',
                                        'Bremen' => 'Bremen',
                                        'Hamburg' => 'Hamburg',
                                        'Hessen' => 'Hessen',
                                        'Mecklenburg-Vorpommern' => 'Mecklenburg-Vorpommern',
                                        'Niedersachsen' => 'Niedersachsen',
                                        'Nordrhein-Westfalen' => 'Nordrhein-Westfalen',
                                        'Rheinland-Pfalz' => 'Rheinland-Pfalz',
                                        'Saarland' => 'Saarland',
                                        'Sachsen' => 'Sachsen',
                                        'Sachsen-Anhalt' => 'Sachsen-Anhalt',
                                        'Schleswig-Holstein' => 'Schleswig-Holstein',
                                        'Thüringen' => 'Thüringen',
                                    ];
                                }
                                return [];
                            })
                            ->visible(fn (Forms\Get $get) => $get('country') === 'Deutschland')
                            ->searchable(),
                        Forms\Components\Hidden::make('country_code')
                            ->default('DE'),
                    ])->columns(2),

                Forms\Components\Section::make('Steuerliche Daten')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Steuernummer')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vat_id')
                            ->label('Umsatzsteuer-ID')
                            ->maxLength(255)
                            ->placeholder('z.B. DE123456789'),
                    ])->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('customer_type') === 'business'),

                Forms\Components\Section::make('Zahlungsbedingungen')
                    ->schema([
                        Forms\Components\TextInput::make('payment_terms')
                            ->label('Zahlungsbedingungen')
                            ->maxLength(255)
                            ->placeholder('z.B. Zahlung innerhalb von 30 Tagen netto'),
                        Forms\Components\TextInput::make('payment_days')
                            ->label('Zahlungsziel (Tage)')
                            ->numeric()
                            ->default(14)
                            ->minValue(0)
                            ->maxValue(365),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bankname')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('iban')
                            ->label('IBAN')
                            ->maxLength(34)
                            ->placeholder('DE89 3704 0044 0532 0130 00'),
                        Forms\Components\TextInput::make('bic')
                            ->label('BIC')
                            ->maxLength(11)
                            ->placeholder('COBADEFFXXX'),
                    ])->columns(2),


                Forms\Components\Section::make('Lexoffice-Synchronisation')
                    ->schema([
                        Forms\Components\TextInput::make('lexoffice_id')
                            ->label('Lexoffice-ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('lexoffice_synced_at')
                            ->label('Zuletzt synchronisiert')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2)
                    ->visible(fn ($record) => $record?->lexoffice_id),

                Forms\Components\Section::make('Zusätzliche Felder')
                    ->schema(\App\Models\DummyFieldConfig::getDummyFieldsSchema('customer'))
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Status & Sonstiges')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierung wird automatisch mit Datum protokolliert'),
                        Forms\Components\DateTimePicker::make('deactivated_at')
                            ->label('Deaktiviert am')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (Forms\Get $get, $record) => !$get('is_active') || $record?->deactivated_at)
                            ->helperText('Wird automatisch gesetzt, wenn Kunde deaktiviert wird'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Kundendaten')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('customer_type')
                            ->label('Kundentyp')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'business' => 'Firmenkunde',
                                'private' => 'Privatkunde',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('ranking')
                            ->label('Ranking')
                            ->formatStateUsing(fn (?string $state): string => $state ? $state . ' Kunde' : '-')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'A' => 'success',
                                'B' => 'info',
                                'C' => 'warning',
                                'D' => 'danger',
                                'E' => 'gray',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('customer_number')
                            ->label('Kundennummer'),
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label(fn ($record) => $record->customer_type === 'private' ? 'Name' : 'Firmenname'),
                        \Filament\Infolists\Components\TextEntry::make('company_name')
                            ->label('Firmenname')
                            ->visible(fn ($record) => $record->customer_type === 'business'),
                        \Filament\Infolists\Components\TextEntry::make('contact_person')
                            ->label('Ansprechpartner')
                            ->visible(fn ($record) => $record->customer_type === 'business'),
                        \Filament\Infolists\Components\TextEntry::make('department')
                            ->label('Abteilung')
                            ->visible(fn ($record) => $record->customer_type === 'business'),
                        \Filament\Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail')
                            ->copyable()
                            ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                            ->openUrlInNewTab(false),
                        \Filament\Infolists\Components\TextEntry::make('phone')
                            ->label('Telefon')
                            ->copyable()
                            ->url(fn ($record) => $record->phone ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone) : null)
                            ->openUrlInNewTab(false),
                        \Filament\Infolists\Components\TextEntry::make('fax')
                            ->label('Fax')
                            ->visible(fn ($record) => $record->customer_type === 'business'),
                        \Filament\Infolists\Components\TextEntry::make('website')
                            ->label('Website')
                            ->url(fn ($record) => $record->website)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->customer_type === 'business'),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make('Standard-Adresse')
                    ->description(fn ($record) => $record->hasSeparateBillingAddress()
                        ? 'Diese Adresse wird als Standard verwendet. Separate Rechnungsadresse ist hinterlegt.'
                        : 'Diese Adresse wird für alle Zwecke verwendet (Standard, Rechnung, Lieferung).')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('street')
                            ->label('Straße & Hausnummer'),
                        \Filament\Infolists\Components\TextEntry::make('address_line_2')
                            ->label('Adresszusatz'),
                        \Filament\Infolists\Components\TextEntry::make('postal_code')
                            ->label('PLZ'),
                        \Filament\Infolists\Components\TextEntry::make('city')
                            ->label('Stadt'),
                        \Filament\Infolists\Components\TextEntry::make('state')
                            ->label('Bundesland/Region'),
                        \Filament\Infolists\Components\TextEntry::make('country')
                            ->label('Land'),
                        \Filament\Infolists\Components\TextEntry::make('country_code')
                            ->label('Ländercode')
                            ->badge(),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make('Rechnungsadresse')
                    ->description(function ($record) {
                        if ($record->hasSeparateBillingAddress()) {
                            return 'Separate Rechnungsadresse für ZUGFeRD-Rechnungen ist hinterlegt.';
                        } elseif ($record->billingAddress) {
                            return 'Rechnungsadresse wurde von Lexoffice importiert.';
                        } else {
                            return 'Keine separate Rechnungsadresse. Standard-Adresse wird für Rechnungen verwendet.';
                        }
                    })
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('billing_address_display')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                if ($record->billingAddress) {
                                    $addr = $record->billingAddress;
                                    $address = $addr->street_address . ',';
                                    if ($addr->address_line_2) $address .= "\n" . $addr->address_line_2;
                                    $address .= "\n" . $addr->postal_code . ' ' . $addr->city;
                                    if ($addr->state) $address .= ', ' . $addr->state;
                                    if ($addr->country !== 'Deutschland') $address .= "\n" . $addr->country;
                                    
                                    // Prüfe ob es eine manuell erstellte separate Adresse ist oder von Lexoffice importiert
                                    // Wenn der Kunde eine Lexoffice-ID hat und synchronisiert wurde, ist es wahrscheinlich importiert
                                    if ($record->lexoffice_id && $record->lexoffice_synced_at) {
                                        return $address . "\n\n(Importiert von Lexoffice)";
                                    } else {
                                        return $address;
                                    }
                                }
                                return 'Keine separate Rechnungsadresse hinterlegt';
                            })
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('manage_billing_address')
                            ->label(function ($record) {
                                if ($record->hasSeparateBillingAddress() || $record->billingAddress) {
                                    return 'Rechnungsadresse bearbeiten';
                                }
                                return 'Rechnungsadresse hinzufügen';
                            })
                            ->icon(function ($record) {
                                if ($record->hasSeparateBillingAddress() || $record->billingAddress) {
                                    return 'heroicon-o-pencil';
                                }
                                return 'heroicon-o-plus';
                            })
                            ->color(function ($record) {
                                if ($record->hasSeparateBillingAddress() || $record->billingAddress) {
                                    return 'warning';
                                }
                                return 'success';
                            })
                            ->form([
                                Forms\Components\TextInput::make('street_address')
                                    ->label('Straße & Hausnummer')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('PLZ')
                                    ->required()
                                    ->maxLength(10),
                                Forms\Components\TextInput::make('city')
                                    ->label('Stadt')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('state')
                                    ->label('Bundesland/Region')
                                    ->maxLength(255),
                                Forms\Components\Select::make('country')
                                    ->label('Land')
                                    ->options([
                                        'Deutschland' => 'Deutschland',
                                        'Österreich' => 'Österreich',
                                        'Schweiz' => 'Schweiz',
                                        'Frankreich' => 'Frankreich',
                                        'Italien' => 'Italien',
                                        'Niederlande' => 'Niederlande',
                                        'Belgien' => 'Belgien',
                                        'Luxemburg' => 'Luxemburg',
                                        'Dänemark' => 'Dänemark',
                                        'Schweden' => 'Schweden',
                                        'Norwegen' => 'Norwegen',
                                        'Finnland' => 'Finnland',
                                        'Polen' => 'Polen',
                                        'Tschechien' => 'Tschechien',
                                        'Slowakei' => 'Slowakei',
                                        'Ungarn' => 'Ungarn',
                                        'Slowenien' => 'Slowenien',
                                        'Kroatien' => 'Kroatien',
                                        'Spanien' => 'Spanien',
                                        'Portugal' => 'Portugal',
                                        'Vereinigtes Königreich' => 'Vereinigtes Königreich',
                                        'Irland' => 'Irland',
                                        'Vereinigte Staaten' => 'Vereinigte Staaten',
                                        'Kanada' => 'Kanada',
                                        'Australien' => 'Australien',
                                        'Neuseeland' => 'Neuseeland',
                                        'Japan' => 'Japan',
                                        'Südkorea' => 'Südkorea',
                                        'China' => 'China',
                                        'Indien' => 'Indien',
                                        'Brasilien' => 'Brasilien',
                                        'Mexiko' => 'Mexiko',
                                        'Argentinien' => 'Argentinien',
                                        'Chile' => 'Chile',
                                        'Südafrika' => 'Südafrika',
                                        'Ägypten' => 'Ägypten',
                                        'Israel' => 'Israel',
                                        'Türkei' => 'Türkei',
                                        'Russland' => 'Russland',
                                        'Ukraine' => 'Ukraine',
                                        'Weißrussland' => 'Weißrussland',
                                        'Serbien' => 'Serbien',
                                        'Bosnien und Herzegowina' => 'Bosnien und Herzegowina',
                                        'Montenegro' => 'Montenegro',
                                        'Nordmazedonien' => 'Nordmazedonien',
                                        'Albanien' => 'Albanien',
                                        'Bulgarien' => 'Bulgarien',
                                        'Rumänien' => 'Rumänien',
                                        'Moldau' => 'Moldau',
                                        'Litauen' => 'Litauen',
                                        'Lettland' => 'Lettland',
                                        'Estland' => 'Estland',
                                        'Griechenland' => 'Griechenland',
                                        'Zypern' => 'Zypern',
                                        'Malta' => 'Malta',
                                        'Island' => 'Island',
                                    ])
                                    ->default('Deutschland')
                                    ->searchable()
                                    ->required(),
                            ])
                            ->fillForm(function ($record) {
                                $billingAddress = $record->billingAddress;
                                if ($billingAddress) {
                                    return [
                                        'street_address' => $billingAddress->street_address,
                                        'postal_code' => $billingAddress->postal_code,
                                        'city' => $billingAddress->city,
                                        'state' => $billingAddress->state,
                                        'country' => $billingAddress->country,
                                    ];
                                }
                                return [
                                    'country' => 'Deutschland',
                                ];
                            })
                            ->action(function ($record, array $data, $livewire) {
                                $billingAddress = $record->billingAddress;
                                
                                if ($billingAddress) {
                                    // Bestehende Adresse aktualisieren
                                    $billingAddress->update([
                                        'street_address' => $data['street_address'],
                                        'postal_code' => $data['postal_code'],
                                        'city' => $data['city'],
                                        'state' => $data['state'],
                                        'country' => $data['country'],
                                    ]);
                                } else {
                                    // Neue Adresse erstellen
                                    $record->addresses()->create([
                                        'type' => 'billing',
                                        'street_address' => $data['street_address'],
                                        'postal_code' => $data['postal_code'],
                                        'city' => $data['city'],
                                        'state' => $data['state'],
                                        'country' => $data['country'],
                                        'is_primary' => false,
                                    ]);
                                }
                                
                                // Automatische Lexoffice-Synchronisation wenn Lexoffice-ID vorhanden
                                $lexofficeMessage = '';
                                if ($record->lexoffice_id) {
                                    try {
                                        $lexofficeService = new LexofficeService();
                                        
                                        // Verwende direkte Synchronisation mit gespeicherter Version wenn verfügbar
                                        if ($record->lexware_version && $record->lexware_json) {
                                            $syncResult = $lexofficeService->exportCustomerWithStoredVersion($record);
                                        } else {
                                            // Fallback auf normale Synchronisation
                                            $syncResult = $lexofficeService->syncCustomer($record);
                                        }
                                        
                                        if ($syncResult['success']) {
                                            $action = $syncResult['action'] ?? 'synchronisiert';
                                            $versionInfo = '';
                                            
                                            if (isset($syncResult['old_version']) && isset($syncResult['new_version'])) {
                                                $versionInfo = " (Version {$syncResult['old_version']} → {$syncResult['new_version']})";
                                            }
                                            
                                            $lexofficeMessage = ' und automatisch in Lexoffice ' . $action . $versionInfo;
                                        } else {
                                            $lexofficeMessage = ' (Lexoffice-Synchronisation fehlgeschlagen: ' . $syncResult['error'] . ')';
                                        }
                                    } catch (\Exception $e) {
                                        $lexofficeMessage = ' (Lexoffice-Synchronisation fehlgeschlagen: ' . $e->getMessage() . ')';
                                    }
                                }
                                
                                // Refresh der Livewire-Komponente
                                if (method_exists($livewire, '$refresh')) {
                                    $livewire->$refresh();
                                }
                                
                                Notification::make()
                                    ->title('Rechnungsadresse gespeichert')
                                    ->body('Die Rechnungsadresse wurde erfolgreich ' . ($billingAddress ? 'aktualisiert' : 'erstellt') . $lexofficeMessage . '.')
                                    ->success()
                                    ->send();
                            })
                            ->modalHeading(function ($record) {
                                if ($record->hasSeparateBillingAddress() || $record->billingAddress) {
                                    return 'Rechnungsadresse bearbeiten';
                                }
                                return 'Rechnungsadresse hinzufügen';
                            })
                            ->modalSubmitActionLabel('Speichern')
                            ->modalWidth('lg'),
                    ])
                    ->collapsible()
                    ->collapsed(function ($record) {
                        // Nicht kollabieren wenn eine Rechnungsadresse vorhanden ist (separate oder importierte)
                        return !($record->hasSeparateBillingAddress() || $record->billingAddress);
                    }),

                \Filament\Infolists\Components\Section::make('Lieferadresse')
                    ->description(function ($record) {
                        if ($record->shippingAddress) {
                            if ($record->lexoffice_id && $record->lexoffice_synced_at) {
                                return 'Lieferadresse wurde von Lexoffice importiert.';
                            } else {
                                return 'Separate Lieferadresse für Installationen ist hinterlegt.';
                            }
                        } else {
                            return 'Keine separate Lieferadresse. Standard-Adresse wird für Lieferungen verwendet.';
                        }
                    })
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('shipping_address_display')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                if ($record->shippingAddress) {
                                    $addr = $record->shippingAddress;
                                    $address = $addr->street_address . ',';
                                    if ($addr->address_line_2) $address .= "\n" . $addr->address_line_2;
                                    $address .= "\n" . $addr->postal_code . ' ' . $addr->city;
                                    if ($addr->state) $address .= ', ' . $addr->state;
                                    if ($addr->country !== 'Deutschland') $address .= "\n" . $addr->country;
                                    
                                    // Prüfe ob es eine von Lexoffice importierte Adresse ist
                                    if ($record->lexoffice_id && $record->lexoffice_synced_at) {
                                        return $address . "\n\n(Importiert von Lexoffice)";
                                    } else {
                                        return $address;
                                    }
                                }
                                return 'Keine separate Lieferadresse hinterlegt';
                            })
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('manage_shipping_address')
                            ->label(fn ($record) => $record->shippingAddress ? 'Lieferadresse bearbeiten' : 'Lieferadresse hinzufügen')
                            ->icon(fn ($record) => $record->shippingAddress ? 'heroicon-o-pencil' : 'heroicon-o-plus')
                            ->color(fn ($record) => $record->shippingAddress ? 'warning' : 'success')
                            ->form([
                                Forms\Components\TextInput::make('street_address')
                                    ->label('Straße & Hausnummer')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('PLZ')
                                    ->required()
                                    ->maxLength(10),
                                Forms\Components\TextInput::make('city')
                                    ->label('Stadt')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('state')
                                    ->label('Bundesland/Region')
                                    ->maxLength(255),
                                Forms\Components\Select::make('country')
                                    ->label('Land')
                                    ->options([
                                        'Deutschland' => 'Deutschland',
                                        'Österreich' => 'Österreich',
                                        'Schweiz' => 'Schweiz',
                                        'Frankreich' => 'Frankreich',
                                        'Italien' => 'Italien',
                                        'Niederlande' => 'Niederlande',
                                        'Belgien' => 'Belgien',
                                        'Luxemburg' => 'Luxemburg',
                                        'Dänemark' => 'Dänemark',
                                        'Schweden' => 'Schweden',
                                        'Norwegen' => 'Norwegen',
                                        'Finnland' => 'Finnland',
                                        'Polen' => 'Polen',
                                        'Tschechien' => 'Tschechien',
                                        'Slowakei' => 'Slowakei',
                                        'Ungarn' => 'Ungarn',
                                        'Slowenien' => 'Slowenien',
                                        'Kroatien' => 'Kroatien',
                                        'Spanien' => 'Spanien',
                                        'Portugal' => 'Portugal',
                                        'Vereinigtes Königreich' => 'Vereinigtes Königreich',
                                        'Irland' => 'Irland',
                                        'Vereinigte Staaten' => 'Vereinigte Staaten',
                                        'Kanada' => 'Kanada',
                                        'Australien' => 'Australien',
                                        'Neuseeland' => 'Neuseeland',
                                        'Japan' => 'Japan',
                                        'Südkorea' => 'Südkorea',
                                        'China' => 'China',
                                        'Indien' => 'Indien',
                                        'Brasilien' => 'Brasilien',
                                        'Mexiko' => 'Mexiko',
                                        'Argentinien' => 'Argentinien',
                                        'Chile' => 'Chile',
                                        'Südafrika' => 'Südafrika',
                                        'Ägypten' => 'Ägypten',
                                        'Israel' => 'Israel',
                                        'Türkei' => 'Türkei',
                                        'Russland' => 'Russland',
                                        'Ukraine' => 'Ukraine',
                                        'Weißrussland' => 'Weißrussland',
                                        'Serbien' => 'Serbien',
                                        'Bosnien und Herzegowina' => 'Bosnien und Herzegowina',
                                        'Montenegro' => 'Montenegro',
                                        'Nordmazedonien' => 'Nordmazedonien',
                                        'Albanien' => 'Albanien',
                                        'Bulgarien' => 'Bulgarien',
                                        'Rumänien' => 'Rumänien',
                                        'Moldau' => 'Moldau',
                                        'Litauen' => 'Litauen',
                                        'Lettland' => 'Lettland',
                                        'Estland' => 'Estland',
                                        'Griechenland' => 'Griechenland',
                                        'Zypern' => 'Zypern',
                                        'Malta' => 'Malta',
                                        'Island' => 'Island',
                                    ])
                                    ->default('Deutschland')
                                    ->searchable()
                                    ->required(),
                            ])
                            ->fillForm(function ($record) {
                                $shippingAddress = $record->shippingAddress;
                                if ($shippingAddress) {
                                    return [
                                        'street_address' => $shippingAddress->street_address,
                                        'postal_code' => $shippingAddress->postal_code,
                                        'city' => $shippingAddress->city,
                                        'state' => $shippingAddress->state,
                                        'country' => $shippingAddress->country,
                                    ];
                                }
                                return [
                                    'country' => 'Deutschland',
                                ];
                            })
                            ->action(function ($record, array $data, $livewire) {
                                $shippingAddress = $record->shippingAddress;
                                
                                if ($shippingAddress) {
                                    // Bestehende Adresse aktualisieren
                                    $shippingAddress->update([
                                        'street_address' => $data['street_address'],
                                        'postal_code' => $data['postal_code'],
                                        'city' => $data['city'],
                                        'state' => $data['state'],
                                        'country' => $data['country'],
                                    ]);
                                } else {
                                    // Neue Adresse erstellen
                                    $record->addresses()->create([
                                        'type' => 'shipping',
                                        'street_address' => $data['street_address'],
                                        'postal_code' => $data['postal_code'],
                                        'city' => $data['city'],
                                        'state' => $data['state'],
                                        'country' => $data['country'],
                                        'is_primary' => false,
                                    ]);
                                }
                                
                                // Automatische Lexoffice-Synchronisation wenn Lexoffice-ID vorhanden
                                $lexofficeMessage = '';
                                if ($record->lexoffice_id) {
                                    try {
                                        $lexofficeService = new LexofficeService();
                                        
                                        // Verwende direkte Synchronisation mit gespeicherter Version wenn verfügbar
                                        if ($record->lexware_version && $record->lexware_json) {
                                            $syncResult = $lexofficeService->exportCustomerWithStoredVersion($record);
                                        } else {
                                            // Fallback auf normale Synchronisation
                                            $syncResult = $lexofficeService->syncCustomer($record);
                                        }
                                        
                                        if ($syncResult['success']) {
                                            $action = $syncResult['action'] ?? 'synchronisiert';
                                            $versionInfo = '';
                                            
                                            if (isset($syncResult['old_version']) && isset($syncResult['new_version'])) {
                                                $versionInfo = " (Version {$syncResult['old_version']} → {$syncResult['new_version']})";
                                            }
                                            
                                            $lexofficeMessage = ' und automatisch in Lexoffice ' . $action . $versionInfo;
                                        } else {
                                            $lexofficeMessage = ' (Lexoffice-Synchronisation fehlgeschlagen: ' . $syncResult['error'] . ')';
                                        }
                                    } catch (\Exception $e) {
                                        $lexofficeMessage = ' (Lexoffice-Synchronisation fehlgeschlagen: ' . $e->getMessage() . ')';
                                    }
                                }
                                
                                // Refresh der Livewire-Komponente
                                if (method_exists($livewire, '$refresh')) {
                                    $livewire->$refresh();
                                }
                                
                                Notification::make()
                                    ->title('Lieferadresse gespeichert')
                                    ->body('Die Lieferadresse wurde erfolgreich ' . ($shippingAddress ? 'aktualisiert' : 'erstellt') . $lexofficeMessage . '.')
                                    ->success()
                                    ->send();
                            })
                            ->modalHeading(fn ($record) => $record->shippingAddress ? 'Lieferadresse bearbeiten' : 'Lieferadresse hinzufügen')
                            ->modalSubmitActionLabel('Speichern')
                            ->modalWidth('lg'),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record->shippingAddress),

                \Filament\Infolists\Components\Section::make('Steuerliche Daten')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('tax_number')
                            ->label('Steuernummer'),
                        \Filament\Infolists\Components\TextEntry::make('vat_id')
                            ->label('Umsatzsteuer-ID'),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->customer_type === 'business'),

                \Filament\Infolists\Components\Section::make('Zahlungsbedingungen')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('payment_terms')
                            ->label('Zahlungsbedingungen'),
                        \Filament\Infolists\Components\TextEntry::make('payment_days')
                            ->label('Zahlungsziel (Tage)')
                            ->suffix(' Tage'),
                        \Filament\Infolists\Components\TextEntry::make('bank_name')
                            ->label('Bankname'),
                        \Filament\Infolists\Components\TextEntry::make('iban')
                            ->label('IBAN')
                            ->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('bic')
                            ->label('BIC')
                            ->copyable(),
                    ])->columns(2),



                \Filament\Infolists\Components\Section::make('Lexoffice-Synchronisation')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('lexoffice_id')
                            ->label('Lexoffice-ID')
                            ->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('lexoffice_synced_at')
                            ->label('Zuletzt synchronisiert')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Noch nie synchronisiert'),
                        \Filament\Infolists\Components\TextEntry::make('lexware_version')
                            ->label('Lexware-Version')
                            ->badge()
                            ->color('info')
                            ->placeholder('Keine Version gespeichert'),
                        \Filament\Infolists\Components\TextEntry::make('lexware_data_status')
                            ->label('Lexware-Daten')
                            ->getStateUsing(function ($record) {
                                if ($record->lexware_json) {
                                    $dataSize = strlen(json_encode($record->lexware_json));
                                    return 'Gespeichert (' . round($dataSize / 1024, 1) . ' KB)';
                                }
                                return 'Keine Daten gespeichert';
                            })
                            ->badge()
                            ->color(fn ($record) => $record->lexware_json ? 'success' : 'gray'),
                        \Filament\Infolists\Components\TextEntry::make('sync_status')
                            ->label('Status')
                            ->getStateUsing(function ($record) {
                                if ($record->lexoffice_id) {
                                    return 'Synchronisiert';
                                }
                                return 'Nicht synchronisiert';
                            })
                            ->badge()
                            ->color(fn ($record) => $record->lexoffice_id ? 'success' : 'warning')
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->headerActions([
                        \Filament\Infolists\Components\Actions\Action::make('sync_with_lexoffice')
                            ->label('Synchronisieren')
                            ->icon('heroicon-o-arrow-path')
                            ->color('success')
                            ->action(function ($record, $livewire) {
                                $service = new LexofficeService();
                                $result = $service->syncCustomer($record);
                                
                                if ($result['success']) {
                                    // Aktualisiere das Record um die neuen Daten anzuzeigen
                                    $record->refresh();
                                    
                                    // Refresh der gesamten Livewire-Komponente
                                    if (method_exists($livewire, '$refresh')) {
                                        $livewire->$refresh();
                                    }
                                    
                                    $message = $result['message'] ?? 'Synchronisation erfolgreich';
                                    
                                    Notification::make()
                                        ->title('Synchronisation erfolgreich')
                                        ->body($message)
                                        ->success()
                                        ->send();
                                } elseif (isset($result['conflict']) && $result['conflict']) {
                                    // Synchronisationskonflikt
                                    Notification::make()
                                        ->title('Synchronisationskonflikt erkannt')
                                        ->body("Sowohl lokale als auch Lexoffice-Daten wurden geändert.\n" .
                                               "Lokal: {$result['local_updated']}\n" .
                                               "Lexoffice: {$result['lexoffice_updated']}\n" .
                                               "Letzte Sync: {$result['last_synced']}")
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Synchronisation fehlgeschlagen')
                                        ->body($result['error'])
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->requiresConfirmation()
                            ->modalHeading('Kunde mit Lexoffice synchronisieren')
                            ->modalDescription(function ($record) {
                                if ($record->lexoffice_id) {
                                    return 'Möchten Sie die Kundendaten in Lexoffice aktualisieren?';
                                }
                                return 'Möchten Sie diesen Kunden in Lexoffice erstellen?';
                            })
                            ->modalSubmitActionLabel('Synchronisieren')
                            ->visible(fn () => config('services.lexoffice.api_key')), // Nur anzeigen wenn API Key konfiguriert ist
                    ]),

                \Filament\Infolists\Components\Section::make('Status & Sonstiges')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        \Filament\Infolists\Components\TextEntry::make('deactivated_at')
                            ->label('Deaktiviert am')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie deaktiviert')
                            ->visible(fn ($record) => $record->deactivated_at),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('updated_at')
                            ->label('Zuletzt geändert')
                            ->dateTime('d.m.Y H:i'),
                    ])->columns(2),
           ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_number')
                    ->label('Kundennummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(function ($record) {
                        if ($record->customer_type === 'business' && $record->company_name) {
                            return $record->company_name;
                        }
                        return $record->name;
                    })
                    ->searchable(['name', 'company_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'business' => 'Firma',
                        'private' => 'Privat',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'business' => 'primary',
                        'private' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Ranking')
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' Kunde' : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        'E' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => $record->phone ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone) : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ort')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Land')
                    ->badge(),
                Tables\Columns\IconColumn::make('lexoffice_synced')
                    ->label('Lexoffice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSyncedWithLexoffice())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('has_invoices')
                    ->label('Hat Rechnungen')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasInvoices())
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->hasInvoices()
                        ? 'Hat ' . $record->getInvoiceCount() . ' Rechnung(en) - Löschen nicht möglich'
                        : 'Keine Rechnungen - Löschen möglich'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoices_count')
                    ->label('Rechnungen')
                    ->counts('invoices')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('solar_participations_count')
                    ->label('Solar-Beteiligungen')
                    ->counts('solarParticipations')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_solar_participation')
                    ->label('Gesamtbeteiligung')
                    ->getStateUsing(function ($record) {
                        return $record->solarParticipations->sum('percentage');
                    })
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 2, ',', '.') . '%' : '-')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes_count')
                    ->label('Notizen')
                    ->counts('notes')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->is_active
                        ? 'Aktiv'
                        : 'Deaktiviert' . ($record->deactivated_at ? ' am ' . $record->deactivated_at->format('d.m.Y H:i') : '')
                    ),
                Tables\Columns\TextColumn::make('deactivated_at')
                    ->label('Deaktiviert am')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Kundentyp')
                    ->options([
                        'business' => 'Firmenkunde',
                        'private' => 'Privatkunde',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Nur aktive Kunden')
                    ->falseLabel('Nur deaktivierte Kunden')
                    ->placeholder('Alle Kunden')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                    ),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
                Tables\Filters\Filter::make('has_solar_participations')
                    ->label('Mit Solar-Beteiligungen')
                    ->query(fn (Builder $query): Builder => $query->whereHas('solarParticipations')),
                Tables\Filters\Filter::make('high_solar_participation')
                    ->label('Hohe Solar-Beteiligung (>= 25%)')
                    ->query(fn (Builder $query): Builder => $query->whereHas('solarParticipations', function ($q) {
                        $q->where('percentage', '>=', 25);
                    })),
                Tables\Filters\Filter::make('recently_deactivated')
                    ->label('Kürzlich deaktiviert (letzte 30 Tage)')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', false)
                        ->whereNotNull('deactivated_at')
                        ->where('deactivated_at', '>=', now()->subDays(30))),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $customersWithInvoices = $records->filter(fn (Customer $customer) => $customer->hasInvoices());
                            
                            if ($customersWithInvoices->isNotEmpty()) {
                                $customerNames = $customersWithInvoices->pluck('name')->join(', ');
                                $totalInvoices = $customersWithInvoices->sum(fn (Customer $customer) => $customer->getInvoiceCount());
                                
                                Notification::make()
                                    ->title('Löschen nicht möglich')
                                    ->body("Die folgenden Kunden haben Rechnungen und können nicht gelöscht werden: {$customerNames} (Insgesamt {$totalInvoices} Rechnungen)")
                                    ->danger()
                                    ->send();
                                
                                // Verhindere das Löschen
                                return false;
                            }
                        })
                        ->modalHeading('Kunden löschen')
                        ->modalDescription(function (Collection $records) {
                            $customersWithInvoices = $records->filter(fn (Customer $customer) => $customer->hasInvoices());
                            
                            if ($customersWithInvoices->isNotEmpty()) {
                                $customerNames = $customersWithInvoices->pluck('name')->join(', ');
                                return "Die folgenden Kunden haben Rechnungen und können nicht gelöscht werden: {$customerNames}";
                            }
                            
                            return 'Sind Sie sicher, dass Sie die ausgewählten Kunden löschen möchten?';
                        }),
                ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('export_to_lexoffice')
                        ->label('An Lexoffice senden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->action(function (Customer $record) {
                            $service = new LexofficeService();
                            $result = $service->exportCustomer($record);
                            
                            if ($result['success']) {
                                $actionText = $result['action'] === 'create' ? 'erstellt' : 'aktualisiert';
                                Notification::make()
                                    ->title('Export erfolgreich')
                                    ->body("Kunde wurde in Lexoffice {$actionText}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Export fehlgeschlagen')
                                    ->body($result['error'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Kunde an Lexoffice senden')
                        ->modalDescription(function (Customer $record) {
                            if ($record->lexoffice_id) {
                                return 'Möchten Sie diesen Kunden in Lexoffice aktualisieren?';
                            }
                            return 'Möchten Sie diesen Kunden in Lexoffice erstellen?';
                        })
                        ->modalSubmitActionLabel('Senden'),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Customer $record) {
                            if ($record->hasInvoices()) {
                                Notification::make()
                                    ->title('Löschen nicht möglich')
                                    ->body('Dieser Kunde hat ' . $record->getInvoiceCount() . ' Rechnung(en) und kann nicht gelöscht werden.')
                                    ->danger()
                                    ->send();
                                
                                // Verhindere das Löschen
                                return false;
                            }
                        })
                        ->modalHeading('Kunde löschen')
                        ->modalDescription(fn (Customer $record) =>
                            $record->hasInvoices()
                                ? 'Dieser Kunde hat ' . $record->getInvoiceCount() . ' Rechnung(en) und kann nicht gelöscht werden.'
                                : 'Sind Sie sicher, dass Sie diesen Kunden löschen möchten?'
                        )
                        ->visible(fn (Customer $record) => $record->canBeDeleted()),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_from_lexoffice')
                    ->label('Von Lexoffice importieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $service = new LexofficeService();
                        $result = $service->importCustomers();
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Import erfolgreich')
                                ->body("{$result['imported']} Kunden importiert")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import fehlgeschlagen')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Kunden von Lexoffice importieren')
                    ->modalDescription('Möchten Sie alle Kunden von Lexoffice importieren? Bestehende Kunden werden aktualisiert.')
                    ->modalSubmitActionLabel('Importieren'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SolarPlantsRelationManager::class,
            RelationManagers\ArticlesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\EmployeesRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\MonthlyCreditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
