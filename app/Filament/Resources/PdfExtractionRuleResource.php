<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfExtractionRuleResource\Pages;
use App\Models\PdfExtractionRule;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PdfExtractionRuleResource extends Resource
{
    protected static ?string $model = PdfExtractionRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Extraktionsregeln';

    protected static ?string $modelLabel = 'Extraktionsregel';

    protected static ?string $pluralModelLabel = 'Extraktionsregeln';

    protected static ?string $navigationGroup = 'PDF-Analyse System';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Lieferant')
                            ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('field_name')
                            ->label('Feldname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. invoice_number, total_amount, due_date'),
                        Forms\Components\Select::make('extraction_method')
                            ->label('Extraktionsmethode')
                            ->options([
                                'regex' => 'Regulärer Ausdruck (Regex)',
                                'keyword_search' => 'Schlüsselwort-Suche',
                                'position_based' => 'Positionsbasiert',
                                'table_extraction' => 'Tabellen-Extraktion',
                                'line_pattern' => 'Zeilen-Pattern',
                                'section_extraction' => 'Bereichs-Extraktion',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Textarea::make('extraction_pattern')
                            ->label('Extraktions-Pattern')
                            ->required()
                            ->rows(3)
                            ->placeholder(function (callable $get) {
                                return match ($get('extraction_method')) {
                                    'regex' => 'z.B. /Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
                                    'keyword_search' => 'z.B. Rechnungsnummer:, Invoice Number:',
                                    'position_based' => 'z.B. line:5, column:10-20',
                                    'table_extraction' => 'z.B. table:1, column:Betrag',
                                    'line_pattern' => 'z.B. Gesamtbetrag*EUR',
                                    'section_extraction' => 'z.B. section:Rechnungsdetails',
                                    default => 'Pattern eingeben...',
                                };
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Konfiguration')
                    ->schema([
                        Forms\Components\Select::make('data_type')
                            ->label('Datentyp')
                            ->options([
                                'string' => 'Text',
                                'number' => 'Zahl',
                                'decimal' => 'Dezimalzahl',
                                'date' => 'Datum',
                                'boolean' => 'Ja/Nein',
                                'email' => 'E-Mail',
                                'phone' => 'Telefonnummer',
                                'iban' => 'IBAN',
                                'currency' => 'Währungsbetrag',
                            ])
                            ->default('string')
                            ->required(),
                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Priorität bei der Regel-Auswertung (1 = höchste Priorität)'),
                        Forms\Components\TextInput::make('confidence_threshold')
                            ->label('Confidence-Schwellwert')
                            ->numeric()
                            ->default(0.8)
                            ->step(0.1)
                            ->minValue(0.1)
                            ->maxValue(1.0)
                            ->helperText('Mindest-Confidence für erfolgreiche Extraktion (0.1 - 1.0)'),
                        Forms\Components\Toggle::make('is_required')
                            ->label('Pflichtfeld')
                            ->helperText('Wenn aktiviert, muss dieses Feld erfolgreich extrahiert werden')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Erweiterte Optionen')
                    ->schema([
                        Forms\Components\Textarea::make('fallback_patterns')
                            ->label('Fallback-Pattern')
                            ->rows(3)
                            ->placeholder('Alternative Pattern (ein Pattern pro Zeile)')
                            ->helperText('Wird verwendet, wenn das Haupt-Pattern fehlschlägt'),
                        Forms\Components\TextInput::make('validation_regex')
                            ->label('Validierungs-Regex')
                            ->placeholder('z.B. /^[A-Z0-9\-]+$/ für Rechnungsnummern')
                            ->helperText('Optionale Validierung des extrahierten Werts'),
                        Forms\Components\Textarea::make('transformation_rules')
                            ->label('Transformationsregeln')
                            ->rows(3)
                            ->placeholder('z.B. trim, uppercase, remove_spaces')
                            ->helperText('Transformationen für den extrahierten Wert (eine pro Zeile)'),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Beschreibung der Extraktionsregel'),
                        Forms\Components\Textarea::make('test_examples')
                            ->label('Test-Beispiele')
                            ->rows(4)
                            ->placeholder('Beispiel-Texte für Tests (ein Beispiel pro Zeile)')
                            ->helperText('Beispiele für Texte, aus denen diese Regel Daten extrahieren soll'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_name')
                    ->label('Feldname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('extraction_method')
                    ->label('Methode')
                    ->colors([
                        'primary' => 'regex',
                        'success' => 'keyword_search',
                        'warning' => 'position_based',
                        'danger' => 'table_extraction',
                        'secondary' => 'line_pattern',
                        'info' => 'section_extraction',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'regex' => 'Regex',
                        'keyword_search' => 'Schlüsselwort',
                        'position_based' => 'Position',
                        'table_extraction' => 'Tabelle',
                        'line_pattern' => 'Zeilen-Pattern',
                        'section_extraction' => 'Bereich',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('data_type')
                    ->label('Datentyp')
                    ->colors([
                        'primary' => 'string',
                        'success' => 'number',
                        'warning' => 'decimal',
                        'danger' => 'date',
                        'secondary' => 'boolean',
                        'info' => ['email', 'phone', 'iban', 'currency'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'string' => 'Text',
                        'number' => 'Zahl',
                        'decimal' => 'Dezimal',
                        'date' => 'Datum',
                        'boolean' => 'Ja/Nein',
                        'email' => 'E-Mail',
                        'phone' => 'Telefon',
                        'iban' => 'IBAN',
                        'currency' => 'Währung',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('extraction_pattern')
                    ->label('Pattern')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->extraction_pattern),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('confidence_threshold')
                    ->label('Confidence')
                    ->numeric(decimalPlaces: 1)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Pflicht')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Lieferant')
                    ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('extraction_method')
                    ->label('Extraktionsmethode')
                    ->options([
                        'regex' => 'Regex',
                        'keyword_search' => 'Schlüsselwort',
                        'position_based' => 'Position',
                        'table_extraction' => 'Tabelle',
                        'line_pattern' => 'Zeilen-Pattern',
                        'section_extraction' => 'Bereich',
                    ]),
                Tables\Filters\SelectFilter::make('data_type')
                    ->label('Datentyp')
                    ->options([
                        'string' => 'Text',
                        'number' => 'Zahl',
                        'decimal' => 'Dezimal',
                        'date' => 'Datum',
                        'boolean' => 'Ja/Nein',
                        'email' => 'E-Mail',
                        'phone' => 'Telefon',
                        'iban' => 'IBAN',
                        'currency' => 'Währung',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Pflichtfeld'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test_extraction')
                        ->label('Extraktion testen')
                        ->icon('heroicon-o-beaker')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('test_text')
                                ->label('Test-Text')
                                ->required()
                                ->rows(8)
                                ->placeholder('Geben Sie hier den PDF-Text ein, aus dem extrahiert werden soll...'),
                        ])
                        ->action(function (array $data, PdfExtractionRule $record) {
                            $testText = $data['test_text'];
                            $result = $record->testExtraction($testText);
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Extraktion erfolgreich')
                                    ->body("Extrahierter Wert: {$result['extracted_value']} (Confidence: {$result['confidence']})")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Extraktion fehlgeschlagen')
                                    ->body($result['error'] ?? 'Kein Wert extrahiert.')
                                    ->warning()
                                    ->send();
                            }
                        }),
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
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('priority');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPdfExtractionRules::route('/'),
            'create' => Pages\CreatePdfExtractionRule::route('/create'),
            'view' => Pages\ViewPdfExtractionRule::route('/{record}'),
            'edit' => Pages\EditPdfExtractionRule::route('/{record}/edit'),
        ];
    }

    /**
     * Zugriffskontrolle: Nur Superadmin-Team-Mitglieder haben Zugriff
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }
}