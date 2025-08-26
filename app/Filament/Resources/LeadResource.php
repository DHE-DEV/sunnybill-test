<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Customer;
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

class LeadResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationLabel = 'Kontakte';
    
    protected static ?string $modelLabel = 'Lead';
    
    protected static ?string $pluralModelLabel = 'Lead - Kontakte';

    protected static ?string $navigationGroup = 'Leads';

    protected static ?int $navigationSort = -10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('customer_type', 'lead');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead-Daten')
                    ->schema([
                        Forms\Components\Hidden::make('customer_type')
                            ->default('lead'),
                        Forms\Components\Select::make('ranking')
                            ->label('Lead-Qualifizierung')
                            ->options([
                                'A' => 'Heißer Lead (A)',
                                'B' => 'Warmer Lead (B)',
                                'C' => 'Kalter Lead (C)',
                                'D' => 'Unqualifiziert (D)',
                                'E' => 'Nicht interessiert (E)',
                            ])
                            ->placeholder('Lead-Qualifizierung auswählen')
                            ->helperText('Bewertung der Lead-Qualität und Kaufbereitschaft')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('customer_number')
                            ->label('Lead-Nummer')
                            ->maxLength(255)
                            ->placeholder('Wird automatisch generiert')
                            ->helperText('Leer lassen für automatische Generierung'),
                        Forms\Components\TextInput::make('name')
                            ->label('Firmenname / Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Ansprechpartner')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('department')
                            ->label('Position / Abteilung')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
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

                Forms\Components\Section::make('Zusätzliche Felder')
                    ->schema(\App\Models\DummyFieldConfig::getDummyFieldsSchema('customer'))
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Status & Notizen')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiver Lead')
                            ->default(true)
                            ->helperText('Deaktivierung wird automatisch mit Datum protokolliert'),
                        Forms\Components\DateTimePicker::make('deactivated_at')
                            ->label('Deaktiviert am')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (Forms\Get $get, $record) => !$get('is_active') || $record?->deactivated_at)
                            ->helperText('Wird automatisch gesetzt, wenn Lead deaktiviert wird'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Lead-Notizen')
                            ->rows(4)
                            ->placeholder('Hier können Sie wichtige Informationen zum Lead erfassen...')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Lead-Daten')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('ranking')
                            ->label('Lead-Qualifizierung')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'A' => 'Heißer Lead (A)',
                                'B' => 'Warmer Lead (B)',
                                'C' => 'Kalter Lead (C)',
                                'D' => 'Unqualifiziert (D)',
                                'E' => 'Nicht interessiert (E)',
                                default => $state ? $state . ' Lead' : 'Nicht qualifiziert',
                            })
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'A' => 'danger',   // Rot für heiße Leads
                                'B' => 'warning',  // Orange für warme Leads
                                'C' => 'info',     // Blau für kalte Leads
                                'D' => 'gray',     // Grau für unqualifiziert
                                'E' => 'danger',   // Rot für nicht interessiert
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('customer_number')
                            ->label('Lead-Nummer'),
                        \Filament\Infolists\Components\TextEntry::make('name')
                            ->label('Firmenname / Name'),
                        \Filament\Infolists\Components\TextEntry::make('contact_person')
                            ->label('Ansprechpartner'),
                        \Filament\Infolists\Components\TextEntry::make('department')
                            ->label('Position / Abteilung'),
                        \Filament\Infolists\Components\TextEntry::make('phone')
                            ->label('Telefon')
                            ->copyable()
                            ->url(fn ($record) => $record->phone ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone) : null)
                            ->openUrlInNewTab(false),
                        \Filament\Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail')
                            ->copyable()
                            ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                            ->openUrlInNewTab(false),
                        \Filament\Infolists\Components\TextEntry::make('website')
                            ->label('Website')
                            ->url(fn ($record) => $record->website)
                            ->openUrlInNewTab(),
                    ])->columns(3),

                \Filament\Infolists\Components\Section::make('Adresse')
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

                \Filament\Infolists\Components\Section::make('Status & Zeitstempel')
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
                            ->label('Lead-Notizen')
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
                    ->label('Lead-Nummer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Firmenname / Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Qualifizierung')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'A' => 'Heiß (A)',
                        'B' => 'Warm (B)',
                        'C' => 'Kalt (C)',
                        'D' => 'Unqualifiziert (D)',
                        'E' => 'Nicht interessiert (E)',
                        default => $state ? $state : '-',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'danger',
                        'B' => 'warning',
                        'C' => 'info',
                        'D' => 'gray',
                        'E' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable()
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->phone ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->phone) : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ort')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Land')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->is_active
                        ? 'Aktiver Lead'
                        : 'Inaktiver Lead' . ($record->deactivated_at ? ' (deaktiviert am ' . $record->deactivated_at->format('d.m.Y H:i') . ')' : '')
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ranking')
                    ->label('Lead-Qualifizierung')
                    ->options([
                        'A' => 'Heißer Lead (A)',
                        'B' => 'Warmer Lead (B)',
                        'C' => 'Kalter Lead (C)',
                        'D' => 'Unqualifiziert (D)',
                        'E' => 'Nicht interessiert (E)',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Nur aktive Leads')
                    ->falseLabel('Nur inaktive Leads')
                    ->placeholder('Alle Leads')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                    ),
                Tables\Filters\Filter::make('hot_leads')
                    ->label('Heiße Leads (A + B)')
                    ->query(fn (Builder $query): Builder => $query->whereIn('ranking', ['A', 'B'])),
                Tables\Filters\Filter::make('recently_created')
                    ->label('Neue Leads (letzte 7 Tage)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('convert_to_customer')
                        ->label('Zu Kunde konvertieren')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $converted = 0;
                            foreach ($records as $lead) {
                                $lead->update(['customer_type' => 'business']);
                                $converted++;
                            }
                            
                            Notification::make()
                                ->title('Leads konvertiert')
                                ->body("{$converted} Lead(s) wurden zu Kunden konvertiert.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Leads zu Kunden konvertieren')
                        ->modalDescription('Möchten Sie die ausgewählten Leads zu Geschäftskunden konvertieren?')
                        ->modalSubmitActionLabel('Konvertieren'),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('convert_to_customer')
                        ->label('Zu Kunde konvertieren')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function (Customer $record) {
                            $record->update(['customer_type' => 'business']);
                            
                            Notification::make()
                                ->title('Lead konvertiert')
                                ->body('Lead wurde erfolgreich zu einem Geschäftskunden konvertiert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Lead zu Kunde konvertieren')
                        ->modalDescription('Möchten Sie diesen Lead zu einem Geschäftskunden konvertieren?')
                        ->modalSubmitActionLabel('Konvertieren'),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
