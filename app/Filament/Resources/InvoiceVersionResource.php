<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceVersionResource\Pages;
use App\Models\InvoiceVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceVersionResource extends Resource
{
    protected static ?string $model = InvoiceVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Rechnungsversionen';

    protected static ?string $modelLabel = 'Rechnungsversion';

    protected static ?string $pluralModelLabel = 'Rechnungsversionen';

    protected static ?string $navigationGroup = 'Rechnungen';

    protected static ?int $navigationSort = 12;
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->label('Rechnung')
                    ->relationship('invoice', 'invoice_number')
                    ->required()
                    ->disabled(),
                
                Forms\Components\TextInput::make('version_number')
                    ->label('Versionsnummer')
                    ->required()
                    ->numeric()
                    ->disabled(),
                
                Forms\Components\Textarea::make('change_reason')
                    ->label('Änderungsgrund')
                    ->nullable()
                    ->maxLength(500)
                    ->rows(3)
                    ->disabled(),
                
                Forms\Components\TextInput::make('changed_by')
                    ->label('Geändert von')
                    ->disabled(),
                
                Forms\Components\Toggle::make('is_current')
                    ->label('Aktuelle Version')
                    ->disabled(),
                
                Forms\Components\Section::make('Rechnungsdaten')
                    ->schema([
                        Forms\Components\KeyValue::make('invoice_data')
                            ->label('Rechnungsdaten')
                            ->disabled(),
                    ])
                    ->collapsible(),
                
                Forms\Components\Section::make('Kundendaten')
                    ->schema([
                        Forms\Components\KeyValue::make('customer_data')
                            ->label('Kundendaten')
                            ->disabled(),
                    ])
                    ->collapsible(),
                
                Forms\Components\Section::make('Rechnungsposten')
                    ->schema([
                        Forms\Components\Repeater::make('items_data')
                            ->label('Rechnungsposten')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Beschreibung')
                                    ->disabled(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->disabled(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Einzelpreis')
                                    ->disabled(),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('Steuersatz')
                                    ->disabled(),
                                Forms\Components\TextInput::make('total')
                                    ->label('Gesamtpreis')
                                    ->disabled(),
                            ])
                            ->disabled()
                            ->columns(2)
                            ->itemLabel(function (array $state): ?string {
                                // Zeige Artikelbezeichnung wenn eingeklappt
                                $articleName = $state['article_data']['name'] ??
                                              $state['article_version_data']['name'] ??
                                              $state['description'] ??
                                              'Unbekannter Artikel';
                                
                                $quantity = $state['quantity'] ?? 0;
                                $total = $state['total'] ?? 0;
                                
                                return "{$articleName} (Menge: {$quantity}, Gesamt: " . number_format($total, 2, ',', '.') . " €)";
                            })
                            ->collapsible(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Rechnungsnummer')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->sortable()
                    ->badge(),
                
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kunde')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('formatted_total')
                    ->label('Gesamtsumme')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Anzahl Posten')
                    ->badge(),
                
                Tables\Columns\IconColumn::make('is_current')
                    ->label('Aktuell')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('change_reason')
                    ->label('Änderungsgrund')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('changed_by')
                    ->label('Geändert von')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('current')
                    ->label('Aktuelle Versionen')
                    ->query(fn (Builder $query): Builder => $query->where('is_current', true)),
                
                Tables\Filters\SelectFilter::make('invoice_id')
                    ->label('Rechnung')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('create_copy')
                    ->label('Kopie erstellen')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (InvoiceVersion $record) {
                        $newInvoice = $record->createInvoiceCopy();
                        
                        return redirect()->route('filament.admin.resources.invoices.edit', $newInvoice);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Rechnungskopie erstellen')
                    ->modalDescription('Möchten Sie eine neue Rechnung basierend auf dieser Version erstellen?'),
                
                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF herunterladen')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (InvoiceVersion $record): string => route('invoice.pdf.version', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Keine Bulk-Actions für Versionen
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoiceVersions::route('/'),
            'view' => Pages\ViewInvoiceVersion::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}