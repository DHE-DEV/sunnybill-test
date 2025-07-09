<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractMatchingRuleResource\Pages;
use App\Models\ContractMatchingRule;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractMatchingRuleResource extends Resource
{
    protected static ?string $model = ContractMatchingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Vertrags-Matching';

    protected static ?string $modelLabel = 'Vertrags-Matching-Regel';

    protected static ?string $pluralModelLabel = 'Vertrags-Matching-Regeln';

    protected static ?string $navigationGroup = 'PDF-Analyse System';

    protected static ?int $navigationSort = 3;

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
                        Forms\Components\TextInput::make('rule_name')
                            ->label('Regelname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Vertragsnummer-Matching, Kostenstellen-Zuordnung'),
                        Forms\Components\Select::make('source_field')
                            ->label('Quellfeld')
                            ->options([
                                'invoice_number' => 'Rechnungsnummer',
                                'contract_number' => 'Vertragsnummer',
                                'cost_center' => 'Kostenstelle',
                                'project_code' => 'Projektcode',
                                'reference_number' => 'Referenznummer',
                                'supplier_reference' => 'Lieferanten-Referenz',
                                'description' => 'Beschreibung',
                                'amount' => 'Betrag',
                                'custom_field_1' => 'Benutzerdefiniertes Feld 1',
                                'custom_field_2' => 'Benutzerdefiniertes Feld 2',
                                'custom_field_3' => 'Benutzerdefiniertes Feld 3',
                            ])
                            ->required()
                            ->helperText('Das Feld aus der PDF-Extraktion, das für das Matching verwendet wird'),
                        Forms\Components\Select::make('target_field')
                            ->label('Zielfeld')
                            ->options([
                                'contract_number' => 'Vertragsnummer',
                                'reference_number' => 'Referenznummer',
                                'cost_center' => 'Kostenstelle',
                                'project_code' => 'Projektcode',
                                'description' => 'Beschreibung',
                                'supplier_reference' => 'Lieferanten-Referenz',
                                'notes' => 'Notizen',
                                'custom_identifier' => 'Benutzerdefinierte Kennung',
                            ])
                            ->required()
                            ->helperText('Das Feld im Vertrag, mit dem verglichen wird'),
                    ])->columns(2),

                Forms\Components\Section::make('Matching-Konfiguration')
                    ->schema([
                        Forms\Components\Select::make('match_type')
                            ->label('Match-Typ')
                            ->options([
                                'exact' => 'Exakte Übereinstimmung',
                                'partial' => 'Teilweise Übereinstimmung',
                                'fuzzy' => 'Fuzzy-Matching',
                                'regex' => 'Regulärer Ausdruck',
                                'range' => 'Bereichs-Matching',
                                'contains' => 'Enthält',
                                'starts_with' => 'Beginnt mit',
                                'ends_with' => 'Endet mit',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('match_threshold')
                            ->label('Match-Schwellwert')
                            ->numeric()
                            ->default(0.8)
                            ->step(0.1)
                            ->minValue(0.1)
                            ->maxValue(1.0)
                            ->helperText('Mindest-Ähnlichkeit für erfolgreiche Zuordnung (0.1 - 1.0)')
                            ->visible(fn (callable $get) => in_array($get('match_type'), ['fuzzy', 'partial'])),
                        Forms\Components\Textarea::make('match_pattern')
                            ->label('Match-Pattern')
                            ->rows(3)
                            ->placeholder(function (callable $get) {
                                return match ($get('match_type')) {
                                    'regex' => 'z.B. /^([A-Z]{2}\d{6}).*/',
                                    'range' => 'z.B. min:1000, max:5000',
                                    'contains' => 'z.B. PROJEKT, WARTUNG',
                                    'starts_with' => 'z.B. KST-, PROJ-',
                                    'ends_with' => 'z.B. -2024, -MAIN',
                                    default => 'Pattern eingeben...',
                                };
                            })
                            ->visible(fn (callable $get) => in_array($get('match_type'), ['regex', 'range', 'contains', 'starts_with', 'ends_with'])),
                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Priorität bei der Regel-Auswertung (1 = höchste Priorität)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Erweiterte Optionen')
                    ->schema([
                        Forms\Components\Toggle::make('case_sensitive')
                            ->label('Groß-/Kleinschreibung beachten')
                            ->default(false)
                            ->helperText('Wenn deaktiviert, wird bei der Suche die Groß-/Kleinschreibung ignoriert'),
                        Forms\Components\Toggle::make('normalize_whitespace')
                            ->label('Leerzeichen normalisieren')
                            ->default(true)
                            ->helperText('Mehrfache Leerzeichen werden zu einem zusammengefasst'),
                        Forms\Components\Toggle::make('remove_special_chars')
                            ->label('Sonderzeichen entfernen')
                            ->default(false)
                            ->helperText('Entfernt Sonderzeichen vor dem Vergleich'),
                        Forms\Components\Textarea::make('preprocessing_rules')
                            ->label('Vorverarbeitungsregeln')
                            ->rows(3)
                            ->placeholder('z.B. trim, uppercase, remove_dashes')
                            ->helperText('Transformationen vor dem Matching (eine pro Zeile)'),
                        Forms\Components\Textarea::make('fallback_rules')
                            ->label('Fallback-Regeln')
                            ->rows(3)
                            ->placeholder('Alternative Matching-Strategien')
                            ->helperText('Wird verwendet, wenn die Haupt-Regel fehlschlägt'),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Beschreibung der Matching-Regel'),
                        Forms\Components\Textarea::make('test_examples')
                            ->label('Test-Beispiele')
                            ->rows(4)
                            ->placeholder('Beispiel-Werte für Tests (ein Beispiel pro Zeile)')
                            ->helperText('Beispiele für Werte, die diese Regel matchen soll'),
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
                Tables\Columns\TextColumn::make('rule_name')
                    ->label('Regelname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('source_field')
                    ->label('Quellfeld')
                    ->colors([
                        'primary' => 'invoice_number',
                        'success' => 'contract_number',
                        'warning' => 'cost_center',
                        'danger' => 'project_code',
                        'secondary' => 'reference_number',
                        'info' => ['supplier_reference', 'description', 'amount'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invoice_number' => 'Rechnungsnr.',
                        'contract_number' => 'Vertragsnr.',
                        'cost_center' => 'Kostenstelle',
                        'project_code' => 'Projektcode',
                        'reference_number' => 'Referenznr.',
                        'supplier_reference' => 'Lieferanten-Ref.',
                        'description' => 'Beschreibung',
                        'amount' => 'Betrag',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('target_field')
                    ->label('Zielfeld')
                    ->colors([
                        'primary' => 'contract_number',
                        'success' => 'reference_number',
                        'warning' => 'cost_center',
                        'danger' => 'project_code',
                        'secondary' => 'description',
                        'info' => ['supplier_reference', 'notes', 'custom_identifier'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contract_number' => 'Vertragsnr.',
                        'reference_number' => 'Referenznr.',
                        'cost_center' => 'Kostenstelle',
                        'project_code' => 'Projektcode',
                        'description' => 'Beschreibung',
                        'supplier_reference' => 'Lieferanten-Ref.',
                        'notes' => 'Notizen',
                        'custom_identifier' => 'Benutzerdef.',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('match_type')
                    ->label('Match-Typ')
                    ->colors([
                        'primary' => 'exact',
                        'success' => 'partial',
                        'warning' => 'fuzzy',
                        'danger' => 'regex',
                        'secondary' => 'range',
                        'info' => ['contains', 'starts_with', 'ends_with'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'exact' => 'Exakt',
                        'partial' => 'Teilweise',
                        'fuzzy' => 'Fuzzy',
                        'regex' => 'Regex',
                        'range' => 'Bereich',
                        'contains' => 'Enthält',
                        'starts_with' => 'Beginnt mit',
                        'ends_with' => 'Endet mit',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('match_threshold')
                    ->label('Schwellwert')
                    ->numeric(decimalPlaces: 1)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('source_field')
                    ->label('Quellfeld')
                    ->options([
                        'invoice_number' => 'Rechnungsnummer',
                        'contract_number' => 'Vertragsnummer',
                        'cost_center' => 'Kostenstelle',
                        'project_code' => 'Projektcode',
                        'reference_number' => 'Referenznummer',
                        'supplier_reference' => 'Lieferanten-Referenz',
                        'description' => 'Beschreibung',
                        'amount' => 'Betrag',
                    ]),
                Tables\Filters\SelectFilter::make('match_type')
                    ->label('Match-Typ')
                    ->options([
                        'exact' => 'Exakt',
                        'partial' => 'Teilweise',
                        'fuzzy' => 'Fuzzy',
                        'regex' => 'Regex',
                        'range' => 'Bereich',
                        'contains' => 'Enthält',
                        'starts_with' => 'Beginnt mit',
                        'ends_with' => 'Endet mit',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test_matching')
                        ->label('Matching testen')
                        ->icon('heroicon-o-beaker')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('test_value')
                                ->label('Test-Wert')
                                ->required()
                                ->placeholder('Geben Sie hier den Wert ein, der gematcht werden soll...'),
                            Forms\Components\TextInput::make('contract_value')
                                ->label('Vertrags-Wert')
                                ->required()
                                ->placeholder('Geben Sie hier den Vertrags-Wert zum Vergleich ein...'),
                        ])
                        ->action(function (array $data, ContractMatchingRule $record) {
                            $testValue = $data['test_value'];
                            $contractValue = $data['contract_value'];
                            $result = $record->testMatching($testValue, $contractValue);
                            
                            if ($result['match']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Matching erfolgreich')
                                    ->body("Match-Score: {$result['score']} (Schwellwert: {$record->match_threshold})")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Kein Match')
                                    ->body("Match-Score: {$result['score']} (Schwellwert: {$record->match_threshold})")
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
            'index' => Pages\ListContractMatchingRules::route('/'),
            'create' => Pages\CreateContractMatchingRule::route('/create'),
            'view' => Pages\ViewContractMatchingRule::route('/{record}'),
            'edit' => Pages\EditContractMatchingRule::route('/{record}/edit'),
        ];
    }
}