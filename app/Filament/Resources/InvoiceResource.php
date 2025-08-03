<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Helpers\PriceFormatter;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Article;
use App\Services\LexofficeService;
use App\Services\ZugferdService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Rechnungen';
    
    protected static ?string $modelLabel = 'Rechnung';
    
    protected static ?string $pluralModelLabel = 'Rechnungen';

    protected static ?string $navigationGroup = 'Fakturierung';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rechnungsdaten')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Kunde')
                            ->options(function () {
                                return \App\Models\Customer::whereNull('deactivated_at')
                                    ->orderBy('customer_number')
                                    ->get()
                                    ->mapWithKeys(function ($customer) {
                                        return [$customer->id => $customer->customer_number . ' - ' . $customer->name];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn ($record) => $record && $record->status !== 'draft')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $customer = \App\Models\Customer::find($state);
                                    if ($customer) {
                                        // Berechne Fälligkeitsdatum basierend auf Kundeneinstellungen
                                        $paymentDays = $customer->payment_days ??
                                                      \App\Models\CompanySetting::current()->default_payment_days ??
                                                      14;
                                        $dueDate = now()->addDays($paymentDays);
                                        $set('due_date', $dueDate->format('Y-m-d'));
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('E-Mail')
                                    ->email(),
                            ]),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Rechnungsnummer')
                            ->required()
                            ->default(fn () => Invoice::generateInvoiceNumber())
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Entwurf',
                                'sent' => 'Versendet',
                                'paid' => 'Bezahlt',
                                'canceled' => 'Storniert',
                            ])
                            ->default('draft'),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->default(function () {
                                $companySettings = \App\Models\CompanySetting::current();
                                $defaultPaymentDays = $companySettings->default_payment_days ?? 14;
                                return now()->addDays($defaultPaymentDays);
                            })
                            ->disabled(fn ($record) => $record && $record->status !== 'draft'),
                    ])->columns(3),
                
                Forms\Components\Section::make('Rechnungsposten')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('article_id')
                                    ->label('Artikel')
                                    ->relationship('article', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->disabled(fn ($record) => $record && $record->status !== 'draft')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $article = Article::with('taxRate')->find($state);
                                            if ($article) {
                                                $set('unit_price', $article->price);
                                                // Verwende den Steuersatz aus der TaxRate-Beziehung falls vorhanden
                                                $taxRate = $article->taxRate ? $article->taxRate->rate : $article->tax_rate;
                                                $set('tax_rate', $taxRate);
                                                $set('description', $article->description);
                                                // Speichere die Nachkommastellen-Info für die Anzeige
                                                $set('article_decimal_places', $article->decimal_places ?? 2);
                                                $set('article_total_decimal_places', $article->total_decimal_places ?? 2);
                                                // Speichere den Artikelnamen für die Anzeige im Header
                                                $set('article_name', $article->name);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Menge')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->disabled(fn ($record) => $record && $record->status !== 'draft')
                                    ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                        $set('total', $state * $get('unit_price'))),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Einzelpreis')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->suffix('€')
                                    ->live(onBlur: true)
                                    ->disabled(fn ($record) => $record && $record->status !== 'draft')
                                    ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) =>
                                        $set('total', $get('quantity') * $state))
                                    ->helperText('Wird mit den artikelspezifischen Nachkommastellen angezeigt'),
                                Forms\Components\Hidden::make('article_decimal_places')
                                    ->default(2),
                                Forms\Components\Hidden::make('article_total_decimal_places')
                                    ->default(2),
                                Forms\Components\Hidden::make('article_name'),
                                Forms\Components\Select::make('tax_rate')
                                    ->label('Steuersatz')
                                    ->required()
                                    ->options(function ($get) {
                                        $options = \App\Models\TaxRate::where('is_active', true)
                                            ->orderBy('rate')
                                            ->get()
                                            ->mapWithKeys(function ($taxRate) {
                                                return [$taxRate->rate => $taxRate->name . ' (' . number_format($taxRate->rate * 100, 0) . '%)'];
                                            })
                                            ->toArray();
                                        
                                        // Füge den aktuellen Wert hinzu, falls er nicht in den Optionen ist
                                        $currentValue = $get('tax_rate');
                                        if ($currentValue !== null && !array_key_exists($currentValue, $options)) {
                                            $options[$currentValue] = number_format($currentValue * 100, 0) . '%';
                                        }
                                        
                                        return $options;
                                    })
                                    ->disabled(fn ($record) => $record && $record->status !== 'draft'),
                                Forms\Components\TextInput::make('total')
                                    ->label('Gesamtpreis')
                                    ->required()
                                    ->numeric()
                                    ->step(0.000001)
                                    ->suffix('€')
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->disabled(fn ($record) => $record && $record->status !== 'draft'),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Artikel hinzufügen')
                            ->addAction(
                                fn ($action) => $action
                                    ->color('success')
                                    ->icon('heroicon-o-plus')
                            )
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->collapseAllAction(
                                fn ($action) => $action
                                    ->label('')
                                    ->icon('heroicon-o-chevron-up')
                                    ->tooltip('Alle einklappen')
                            )
                            ->expandAllAction(
                                fn ($action) => $action
                                    ->label('')
                                    ->icon('heroicon-o-chevron-down')
                                    ->tooltip('Alle ausklappen')
                            )
                            ->itemLabel(function (array $state, $component): ?string {
                                // Zeige Artikelname wenn eingeklappt
                                $articleName = $state['article_name'] ?? null;
                                
                                // Fallback: Lade Artikelname aus der Datenbank wenn nicht im State
                                if (!$articleName && isset($state['article_id'])) {
                                    $article = Article::find($state['article_id']);
                                    $articleName = $article?->name;
                                }
                                
                                // Weitere Fallback: Verwende description
                                if (!$articleName) {
                                    $articleName = $state['description'] ?? 'Neuer Artikel';
                                }
                                
                                $quantity = $state['quantity'] ?? 0;
                                $total = $state['total'] ?? 0;
                                
                                if ($articleName !== 'Neuer Artikel') {
                                    return "{$articleName} (Menge: {$quantity}, Gesamt: " . number_format($total, 2, ',', '.') . " €)";
                                }
                                
                                return 'Neuer Artikel';
                            })
                            ->disabled(fn ($record) => $record && $record->status !== 'draft'),
                    ]),
                
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
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.customer_number')
                    ->label('Kundennummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Entwurf',
                        'sent' => 'Versendet',
                        'paid' => 'Bezahlt',
                        'canceled' => 'Storniert',
                        default => $state
                    })
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'danger' => 'canceled',
                    ]),
                Tables\Columns\TextColumn::make('total')
                    ->label('Gesamtsumme')
                    ->formatStateUsing(fn ($state) => PriceFormatter::formatTotalPrice($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Positionen')
                    ->counts('items')
                    ->badge(),
                Tables\Columns\IconColumn::make('lexoffice_synced')
                    ->label('Lexoffice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSyncedWithLexoffice())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Entwurf',
                        'sent' => 'Versendet',
                        'paid' => 'Bezahlt',
                        'canceled' => 'Storniert',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('download_pdf')
                        ->label('PDF herunterladen')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function (Invoice $record) {
                            $pdf = Pdf::loadView('invoices.pdf', compact('record'));
                            return Response::streamDownload(
                                fn () => print($pdf->output()),
                                "rechnung-{$record->invoice_number}.pdf"
                            );
                        }),
                    Tables\Actions\Action::make('download_zugferd_pdf')
                        ->label('ZUGFeRD PDF herunterladen')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Invoice $record) {
                            $zugferdService = new ZugferdService();
                            $pdfContent = $zugferdService->generateZugferdPdf($record);
                            
                            return Response::streamDownload(
                                fn () => print($pdfContent),
                                "rechnung-{$record->invoice_number}-zugferd.pdf",
                                ['Content-Type' => 'application/pdf']
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading('ZUGFeRD PDF herunterladen')
                        ->modalDescription('Möchten Sie eine ZUGFeRD-konforme PDF-Rechnung herunterladen?')
                        ->modalSubmitActionLabel('Herunterladen'),
                    Tables\Actions\Action::make('download_zugferd_xml')
                        ->label('ZUGFeRD XML herunterladen')
                        ->icon('heroicon-o-code-bracket')
                        ->color('info')
                        ->action(function (Invoice $record) {
                            $zugferdService = new ZugferdService();
                            $xmlContent = $zugferdService->generateZugferdXml($record);
                            
                            return Response::streamDownload(
                                fn () => print($xmlContent),
                                "rechnung-{$record->invoice_number}-zugferd.xml",
                                ['Content-Type' => 'application/xml']
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading('ZUGFeRD XML herunterladen')
                        ->modalDescription('Möchten Sie die ZUGFeRD XML-Datei herunterladen?')
                        ->modalSubmitActionLabel('Herunterladen'),
                    Tables\Actions\Action::make('send_to_lexoffice')
                        ->label('An Lexoffice senden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn (Invoice $record) => $record->canBeSentToLexoffice())
                        ->action(function (Invoice $record) {
                            $service = new LexofficeService();
                            $result = $service->exportInvoice($record);
                            
                            if ($result['success']) {
                                Notification::make()
                                    ->title('Export erfolgreich')
                                    ->body('Rechnung wurde an Lexoffice gesendet')
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
                        ->modalHeading('Rechnung an Lexoffice senden')
                        ->modalDescription('Möchten Sie diese Rechnung an Lexoffice senden?')
                        ->modalSubmitActionLabel('Senden'),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Invoice $record) => $record->status === 'draft'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (Invoice $record) => $record->status === 'draft'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            // Nur Entwürfe können gelöscht werden
                            $draftRecords = $records->filter(fn ($record) => $record->status === 'draft');
                            $draftRecords->each->delete();
                            
                            $nonDraftCount = $records->count() - $draftRecords->count();
                            if ($nonDraftCount > 0) {
                                Notification::make()
                                    ->title('Teilweise gelöscht')
                                    ->body("{$nonDraftCount} Rechnung(en) konnten nicht gelöscht werden (nur Entwürfe können gelöscht werden)")
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('invoice_number', 'desc');
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
