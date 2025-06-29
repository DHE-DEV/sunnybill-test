<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Helpers\PriceFormatter;
use App\Models\Article;
use App\Models\TaxRate;
use App\Services\LexofficeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationLabel = 'Artikel';
    
    protected static ?string $modelLabel = 'Artikel';
    
    protected static ?string $pluralModelLabel = 'Artikel';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Artikeldaten')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->required()
                            ->options([
                                'SERVICE' => 'Dienstleistung',
                                'PRODUCT' => 'Produkt',
                            ])
                            ->default('SERVICE')
                            ->helperText('Typ für Lexoffice Export'),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Preise & Steuern')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Preis (netto)')
                            ->required()
                            ->numeric()
                            ->step(0.000001)
                            ->suffix('€')
                            ->helperText('Negative Werte erlaubt (z.B. -0,081234 für Einspeisevergütung). Anzeige erfolgt mit konfigurierten Nachkommastellen.'),
                        Forms\Components\Select::make('tax_rate_id')
                            ->label('Steuersatz')
                            ->required()
                            ->relationship('taxRate', 'name')
                            ->getOptionLabelFromRecordUsing(fn (TaxRate $record): string =>
                                "{$record->name} ({$record->current_rate}%)"
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('rate')
                                    ->label('Steuersatz (%)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : null),
                                Forms\Components\DatePicker::make('valid_from')
                                    ->label('Gültig ab')
                                    ->required()
                                    ->default(now())
                                    ->native(false),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ])
                            ->helperText('Wählen Sie einen Steuersatz aus oder erstellen Sie einen neuen'),
                        Forms\Components\TextInput::make('unit')
                            ->label('Einheit')
                            ->required()
                            ->default('Stück')
                            ->maxLength(50),
                        Forms\Components\Select::make('decimal_places')
                            ->label('Einzelpreis Nachkommastellen')
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
                            ->helperText('Anzahl der Nachkommastellen für die Einzelpreis-Anzeige dieses Artikels'),
                        Forms\Components\Select::make('total_decimal_places')
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
                            ->helperText('Anzahl der Nachkommastellen für Gesamtpreise in Rechnungen mit diesem Artikel'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Lexoffice')
                    ->schema([
                        Forms\Components\TextInput::make('lexoffice_id')
                            ->label('Lexoffice ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])->visible(fn ($record) => $record?->lexoffice_id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'SERVICE' => 'Dienstleistung',
                        'PRODUCT' => 'Produkt',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'SERVICE' => 'info',
                        'PRODUCT' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis (netto)')
                    ->formatStateUsing(fn ($record) => $record->formatted_price)
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Einheit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('decimal_places')
                    ->label('Einzelpreis NK')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state . ' NK'),
                Tables\Columns\TextColumn::make('total_decimal_places')
                    ->label('Gesamtpreis NK')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state . ' NK'),
                Tables\Columns\TextColumn::make('gross_price')
                    ->label('Preis (brutto)')
                    ->formatStateUsing(fn ($record) => $record->formatted_gross_price)
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxRate.name')
                    ->label('Steuersatz')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($record) =>
                        $record->taxRate ?
                        "{$record->taxRate->name} ({$record->taxRate->current_rate}%)" :
                        'Kein Steuersatz'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('lexoffice_synced')
                    ->label('Lexoffice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSyncedWithLexoffice())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('used_in_invoices')
                    ->label('In Rechnungen')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isUsedInInvoices())
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->isUsedInInvoices()
                        ? 'Verwendet in ' . $record->getInvoiceUsageCount() . ' Rechnung(en) - Löschen nicht möglich'
                        : 'Nicht in Rechnungen verwendet - Löschen möglich'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'SERVICE' => 'Dienstleistung',
                        'PRODUCT' => 'Produkt',
                    ]),
                Tables\Filters\SelectFilter::make('tax_rate_id')
                    ->label('Steuersatz')
                    ->relationship('taxRate', 'name')
                    ->getOptionLabelFromRecordUsing(fn (TaxRate $record): string =>
                        "{$record->name} ({$record->current_rate}%)"
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
                Tables\Filters\TernaryFilter::make('used_in_invoices')
                    ->label('In Rechnungen verwendet')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('invoiceItems'),
                        false: fn (Builder $query) => $query->whereDoesntHave('invoiceItems'),
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'SERVICE' => 'Dienstleistung',
                        'PRODUCT' => 'Produkt',
                    ]),
                Tables\Filters\SelectFilter::make('tax_rate_id')
                    ->label('Steuersatz')
                    ->relationship('taxRate', 'name')
                    ->getOptionLabelFromRecordUsing(fn (TaxRate $record): string =>
                        "{$record->name} ({$record->current_rate}%)"
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
                Tables\Filters\TernaryFilter::make('used_in_invoices')
                    ->label('In Rechnungen verwendet')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('invoiceItems'),
                        false: fn (Builder $query) => $query->whereDoesntHave('invoiceItems'),
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\Action::make('export_to_lexoffice')
                        ->label('An Lexoffice senden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->action(function (Article $record) {
                            $service = new LexofficeService();
                            $result = $service->exportArticle($record);
                            
                            if ($result['success']) {
                                $actionText = $result['action'] === 'create' ? 'erstellt' : 'aktualisiert';
                                Notification::make()
                                    ->title('Export erfolgreich')
                                    ->body("Artikel wurde in Lexoffice {$actionText}")
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
                        ->modalHeading('Artikel an Lexoffice senden')
                        ->modalDescription(function (Article $record) {
                            if ($record->lexoffice_id) {
                                return 'Möchten Sie diesen Artikel in Lexoffice aktualisieren?';
                            }
                            return 'Möchten Sie diesen Artikel in Lexoffice erstellen?';
                        })
                        ->modalSubmitActionLabel('Senden'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_from_lexoffice')
                    ->label('Von Lexoffice importieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $service = new LexofficeService();
                        $result = $service->importArticles();
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Import erfolgreich')
                                ->body("{$result['imported']} Artikel importiert")
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
                    ->modalHeading('Artikel von Lexoffice importieren')
                    ->modalDescription('Möchten Sie alle Artikel von Lexoffice importieren? Bestehende Artikel werden aktualisiert.')
                    ->modalSubmitActionLabel('Importieren'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
