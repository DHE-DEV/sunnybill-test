<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierRecognitionPatternResource\Pages;
use App\Models\SupplierRecognitionPattern;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierRecognitionPatternResource extends Resource
{
    protected static ?string $model = SupplierRecognitionPattern::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Erkennungspattern';

    protected static ?string $modelLabel = 'Erkennungspattern';

    protected static ?string $pluralModelLabel = 'Erkennungspattern';

    protected static ?string $navigationGroup = 'PDF-Analyse System';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Superadmin'])->exists() ?? false;
    }

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
                        Forms\Components\Select::make('pattern_type')
                            ->label('Pattern-Typ')
                            ->options([
                                'email_domain' => 'E-Mail-Domain',
                                'company_name' => 'Firmenname',
                                'tax_id' => 'Steuer-ID',
                                'address_pattern' => 'Adress-Pattern',
                                'phone_pattern' => 'Telefon-Pattern',
                                'website_pattern' => 'Website-Pattern',
                                'custom_text' => 'Benutzerdefinierter Text',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('pattern_value')
                            ->label('Pattern-Wert')
                            ->required()
                            ->maxLength(500)
                            ->placeholder(function (callable $get) {
                                return match ($get('pattern_type')) {
                                    'email_domain' => 'z.B. @eon.de',
                                    'company_name' => 'z.B. E.ON Energie Deutschland',
                                    'tax_id' => 'z.B. DE123456789',
                                    'address_pattern' => 'z.B. E.ON-Platz 1',
                                    'phone_pattern' => 'z.B. +49 201',
                                    'website_pattern' => 'z.B. eon.de',
                                    'custom_text' => 'z.B. spezifischer Text',
                                    default => 'Pattern-Wert eingeben',
                                };
                            }),
                        Forms\Components\Toggle::make('is_regex')
                            ->label('Als Regex behandeln')
                            ->helperText('Wenn aktiviert, wird der Pattern-Wert als regulärer Ausdruck interpretiert')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Konfiguration')
                    ->schema([
                        Forms\Components\TextInput::make('confidence_weight')
                            ->label('Confidence-Gewichtung')
                            ->numeric()
                            ->default(1.0)
                            ->step(0.1)
                            ->minValue(0.1)
                            ->maxValue(10.0)
                            ->helperText('Gewichtung für die Confidence-Berechnung (0.1 - 10.0)'),
                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Priorität bei der Pattern-Auswertung (1 = höchste Priorität)'),
                        Forms\Components\Toggle::make('case_sensitive')
                            ->label('Groß-/Kleinschreibung beachten')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Optionale Beschreibung des Patterns'),
                        Forms\Components\Textarea::make('test_examples')
                            ->label('Test-Beispiele')
                            ->rows(3)
                            ->placeholder('Beispiele für Texte, die dieses Pattern erkennen soll (ein Beispiel pro Zeile)'),
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
                Tables\Columns\BadgeColumn::make('pattern_type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'email_domain',
                        'success' => 'company_name',
                        'warning' => 'tax_id',
                        'danger' => 'address_pattern',
                        'secondary' => 'phone_pattern',
                        'info' => 'website_pattern',
                        'gray' => 'custom_text',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'email_domain' => 'E-Mail-Domain',
                        'company_name' => 'Firmenname',
                        'tax_id' => 'Steuer-ID',
                        'address_pattern' => 'Adress-Pattern',
                        'phone_pattern' => 'Telefon-Pattern',
                        'website_pattern' => 'Website-Pattern',
                        'custom_text' => 'Benutzerdefiniert',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pattern_value')
                    ->label('Pattern-Wert')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_regex')
                    ->label('Regex')
                    ->boolean()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('confidence_weight')
                    ->label('Gewichtung')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('case_sensitive')
                    ->label('Case Sensitive')
                    ->boolean()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
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
                Tables\Filters\SelectFilter::make('pattern_type')
                    ->label('Pattern-Typ')
                    ->options([
                        'email_domain' => 'E-Mail-Domain',
                        'company_name' => 'Firmenname',
                        'tax_id' => 'Steuer-ID',
                        'address_pattern' => 'Adress-Pattern',
                        'phone_pattern' => 'Telefon-Pattern',
                        'website_pattern' => 'Website-Pattern',
                        'custom_text' => 'Benutzerdefiniert',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('is_regex')
                    ->label('Regex'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test_pattern')
                        ->label('Pattern testen')
                        ->icon('heroicon-o-beaker')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('test_text')
                                ->label('Test-Text')
                                ->required()
                                ->rows(5)
                                ->placeholder('Geben Sie hier den Text ein, gegen den das Pattern getestet werden soll...'),
                        ])
                        ->action(function (array $data, SupplierRecognitionPattern $record) {
                            $testText = $data['test_text'];
                            $result = $record->testPattern($testText);
                            
                            if ($result['matches']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Pattern-Test erfolgreich')
                                    ->body("Pattern gefunden! Confidence: {$result['confidence']}")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Pattern-Test fehlgeschlagen')
                                    ->body('Pattern wurde im Test-Text nicht gefunden.')
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
            'index' => Pages\ListSupplierRecognitionPatterns::route('/'),
            'create' => Pages\CreateSupplierRecognitionPattern::route('/create'),
            'view' => Pages\ViewSupplierRecognitionPattern::route('/{record}'),
            'edit' => Pages\EditSupplierRecognitionPattern::route('/{record}/edit'),
        ];
    }
}