<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Adressen';

    protected static ?string $modelLabel = 'Adresse';

    protected static ?string $pluralModelLabel = 'Adressen';

    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adressdaten')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Adresstyp')
                            ->options([
                                'standard' => 'Standard-Adresse',
                                'billing' => 'Rechnungsadresse',
                                'shipping' => 'Lieferadresse',
                            ])
                            ->required()
                            ->default('billing'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Hauptadresse für diesen Typ')
                            ->default(true)
                            ->helperText('Nur eine Adresse pro Typ kann als Hauptadresse markiert werden'),
                        Forms\Components\TextInput::make('street_address')
                            ->label('Straße & Hausnummer')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address_line_2')
                            ->label('Adresszusatz')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('PLZ')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('city')
                            ->label('Stadt')
                            ->required()
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
                                
                                $set('country_code', $countryCodes[$state] ?? '');
                                
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
                        Forms\Components\TextInput::make('country_code')
                            ->label('Ländercode')
                            ->maxLength(2)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Wird automatisch basierend auf dem Land gesetzt'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street_address')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'billing' => 'Rechnung',
                        'shipping' => 'Lieferung',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'billing' => 'warning',
                        'shipping' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Hauptadresse')
                    ->boolean(),
                Tables\Columns\TextColumn::make('street_address')
                    ->label('Straße')
                    ->searchable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->label('PLZ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Land')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Adresstyp')
                    ->options([
                        'standard' => 'Standard-Adresse',
                        'billing' => 'Rechnungsadresse',
                        'shipping' => 'Lieferadresse',
                    ]),
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Hauptadresse'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Adresse'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}