<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SupplierContractBilling;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class BillingsRelationManager extends RelationManager
{
    protected static string $relationship = 'billings';

    protected static ?string $title = 'Abrechnungen';

    protected static ?string $modelLabel = 'Abrechnung';

    protected static ?string $pluralModelLabel = 'Abrechnungen';

    protected static ?string $recordTitleAttribute = 'billing_number';

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->billings()->count();
        return $count > 0 ? (string) $count : null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abrechnungsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('billing_number')
                            ->label('Abrechnungsnummer')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Wird automatisch generiert'),

                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('Anbieter-Rechnungsnummer')
                            ->maxLength(255)
                            ->placeholder('Rechnungsnummer des Anbieters'),

                        Forms\Components\Select::make('billing_type')
                            ->label('Abrechnungstyp')
                            ->options(SupplierContractBilling::getBillingTypeOptions())
                            ->default('invoice')
                            ->required(),

                        Forms\Components\Select::make('billing_year')
                            ->label('Abrechnungsjahr')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                                    $years[$year] = $year;
                                }
                                return $years;
                            })
                            ->default(function () {
                                $lastMonth = now()->subMonth();
                                return $lastMonth->year;
                            })
                            ->searchable(),

                        Forms\Components\Select::make('billing_month')
                            ->label('Abrechnungsmonat')
                            ->options(SupplierContractBilling::getMonthOptions())
                            ->default(function () {
                                $lastMonth = now()->subMonth();
                                return $lastMonth->month;
                            })
                            ->searchable(),

                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('billing_date')
                            ->label('Abrechnungsdatum')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Fälligkeitsdatum')
                            ->after('billing_date'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Gesamtbetrag')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('€')
                            ->minValue(0),

                        Forms\Components\Select::make('currency')
                            ->label('Währung')
                            ->options([
                                'EUR' => 'Euro (€)',
                                'USD' => 'US-Dollar ($)',
                                'CHF' => 'Schweizer Franken (CHF)',
                            ])
                            ->default('EUR')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SupplierContractBilling::getStatusOptions())
                            ->default('draft')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('billing_number')
            ->columns([
                Tables\Columns\TextColumn::make('billing_number')
                    ->label('Abrechnungsnummer')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('supplier_invoice_number')
                    ->label('Anbieter-Rechnung')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('—')
                    ->description(fn (SupplierContractBilling $record): ?string =>
                        $record->supplierContract?->supplier?->company_name
                    ),

                Tables\Columns\BadgeColumn::make('billing_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getBillingTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'invoice',
                        'warning' => 'credit_note',
                    ])
                    ->size('sm'),

                Tables\Columns\TextColumn::make('billing_period')
                    ->label('Abrechnungsperiode')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        return $record->billing_period;
                    })
                    ->sortable(['billing_year', 'billing_month'])
                    ->searchable(false)
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (SupplierContractBilling $record): ?string {
                        return strlen($record->title) > 40 ? $record->title : null;
                    })
                    ->description(fn (SupplierContractBilling $record): ?string =>
                        $record->description ? \Illuminate\Support\Str::limit($record->description, 60) : null
                    ),

                Tables\Columns\TextColumn::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->description(fn (SupplierContractBilling $record): ?string =>
                        $record->due_date ? 'Fällig: ' . $record->due_date->format('d.m.Y') : null
                    ),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Gesamtbetrag')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (SupplierContractBilling $record): string =>
                        match($record->status) {
                            'paid' => 'success',
                            'cancelled' => 'danger',
                            'pending' => 'warning',
                            default => 'gray'
                        }
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => SupplierContractBilling::getStatusOptions()[$state] ?? $state)
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'paid',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('allocations_summary')
                    ->label('Kostenträger')
                    ->getStateUsing(function (SupplierContractBilling $record): string {
                        $allocationsCount = $record->allocations()->count();
                        $totalPercentage = $record->allocations()->sum('percentage');
                        
                        if ($allocationsCount === 0) {
                            return 'Keine Aufteilung';
                        }
                        
                        return "{$allocationsCount} Träger ({$totalPercentage}%)";
                    })
                    ->badge()
                    ->color(function (SupplierContractBilling $record): string {
                        $totalPercentage = $record->allocations()->sum('percentage');
                        if ($totalPercentage == 100) return 'success';
                        if ($totalPercentage > 0) return 'warning';
                        return 'danger';
                    })
                    ->tooltip(function (SupplierContractBilling $record): ?string {
                        $allocations = $record->allocations()->with('solarPlant')->get();
                        if ($allocations->isEmpty()) {
                            return 'Keine Kostenträger zugeordnet';
                        }
                        
                        $tooltip = "Kostenträger-Aufteilung:\n";
                        foreach ($allocations as $allocation) {
                            $plantName = $allocation->solarPlant?->name ?? 'Unbekannt';
                            $tooltip .= "• {$plantName}: {$allocation->percentage}% (€{$allocation->amount})\n";
                        }
                        return trim($tooltip);
                    }),

                Tables\Columns\IconColumn::make('has_documents')
                    ->label('Dokumente')
                    ->getStateUsing(fn (SupplierContractBilling $record): bool => $record->documents()->count() > 0)
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(function (SupplierContractBilling $record): string {
                        $count = $record->documents()->count();
                        return $count > 0 ? "{$count} Dokument(e) vorhanden" : 'Keine Dokumente';
                    }),

                Tables\Columns\TextColumn::make('days_until_due')
                    ->label('Fälligkeit')
                    ->getStateUsing(function (SupplierContractBilling $record): ?string {
                        if (!$record->due_date) return null;
                        
                        $daysUntilDue = now()->diffInDays($record->due_date, false);
                        
                        if ($daysUntilDue < 0) {
                            return abs($daysUntilDue) . ' Tage überfällig';
                        } elseif ($daysUntilDue == 0) {
                            return 'Heute fällig';
                        } elseif ($daysUntilDue <= 7) {
                            return 'In ' . $daysUntilDue . ' Tagen';
                        } else {
                            return $record->due_date->format('d.m.Y');
                        }
                    })
                    ->badge()
                    ->color(function (SupplierContractBilling $record): string {
                        if (!$record->due_date) return 'gray';
                        
                        $daysUntilDue = now()->diffInDays($record->due_date, false);
                        
                        if ($daysUntilDue < 0) return 'danger';      // Überfällig
                        if ($daysUntilDue <= 3) return 'warning';    // Bald fällig
                        if ($daysUntilDue <= 7) return 'info';       // Diese Woche
                        return 'gray';                               // Normal
                    })
                    ->sortable('due_date')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn (SupplierContractBilling $record): ?string =>
                        $record->updated_at->ne($record->created_at) ?
                        'Geändert: ' . $record->updated_at->format('d.m.Y H:i') : null
                    ),
            ])
            ->filters([
                // Status Filter
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContractBilling::getStatusOptions())
                    ->multiple()
                    ->preload(),

                // Abrechnungstyp Filter
                SelectFilter::make('billing_type')
                    ->label('Abrechnungstyp')
                    ->options(SupplierContractBilling::getBillingTypeOptions())
                    ->multiple(),

                // Jahr Filter
                SelectFilter::make('billing_year')
                    ->label('Abrechnungsjahr')
                    ->options(function () {
                        $currentYear = now()->year;
                        $years = [];
                        for ($year = $currentYear - 5; $year <= $currentYear + 2; $year++) {
                            $years[$year] = $year;
                        }
                        return $years;
                    })
                    ->multiple()
                    ->default([now()->year]),

                // Monat Filter
                SelectFilter::make('billing_month')
                    ->label('Abrechnungsmonat')
                    ->options(SupplierContractBilling::getMonthOptions())
                    ->multiple(),

                // Quartal Filter
                SelectFilter::make('quarter')
                    ->label('Quartal')
                    ->options([
                        'Q1' => '1. Quartal (Jan-Mär)',
                        'Q2' => '2. Quartal (Apr-Jun)',
                        'Q3' => '3. Quartal (Jul-Sep)',
                        'Q4' => '4. Quartal (Okt-Dez)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) return $query;
                        
                        return $query->where(function ($q) use ($data) {
                            foreach ($data['values'] as $quarter) {
                                $months = match($quarter) {
                                    'Q1' => [1, 2, 3],
                                    'Q2' => [4, 5, 6],
                                    'Q3' => [7, 8, 9],
                                    'Q4' => [10, 11, 12],
                                    default => []
                                };
                                if (!empty($months)) {
                                    $q->orWhereIn('billing_month', $months);
                                }
                            }
                        });
                    })
                    ->multiple(),

                // Betrag Filter
                Filter::make('amount_range')
                    ->label('Betragsspanne')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('Von (€)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('0,00'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('Bis (€)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('999.999,99'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),

                // Datum Filter
                Filter::make('billing_date')
                    ->label('Abrechnungsdatum')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('Von')
                                    ->placeholder('TT.MM.JJJJ'),
                                Forms\Components\DatePicker::make('until')
                                    ->label('Bis')
                                    ->placeholder('TT.MM.JJJJ'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('billing_date', '<=', $date),
                            );
                    }),

                // Fälligkeits-Filter
                Filter::make('due_status')
                    ->label('Fälligkeit')
                    ->form([
                        Forms\Components\Select::make('due_filter')
                            ->label('Fälligkeitsstatus')
                            ->options([
                                'overdue' => 'Überfällig',
                                'due_today' => 'Heute fällig',
                                'due_this_week' => 'Diese Woche fällig',
                                'due_next_week' => 'Nächste Woche fällig',
                                'due_this_month' => 'Diesen Monat fällig',
                                'no_due_date' => 'Ohne Fälligkeitsdatum',
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['due_filter'])) return $query;
                        
                        return $query->where(function ($q) use ($data) {
                            foreach ($data['due_filter'] as $filter) {
                                match($filter) {
                                    'overdue' => $q->orWhere(function ($subQ) {
                                        $subQ->whereNotNull('due_date')
                                             ->where('due_date', '<', now()->startOfDay());
                                    }),
                                    'due_today' => $q->orWhere(function ($subQ) {
                                        $subQ->whereDate('due_date', now());
                                    }),
                                    'due_this_week' => $q->orWhere(function ($subQ) {
                                        $subQ->whereBetween('due_date', [
                                            now()->startOfWeek(),
                                            now()->endOfWeek()
                                        ]);
                                    }),
                                    'due_next_week' => $q->orWhere(function ($subQ) {
                                        $subQ->whereBetween('due_date', [
                                            now()->addWeek()->startOfWeek(),
                                            now()->addWeek()->endOfWeek()
                                        ]);
                                    }),
                                    'due_this_month' => $q->orWhere(function ($subQ) {
                                        $subQ->whereBetween('due_date', [
                                            now()->startOfMonth(),
                                            now()->endOfMonth()
                                        ]);
                                    }),
                                    'no_due_date' => $q->orWhereNull('due_date'),
                                    default => null
                                };
                            }
                        });
                    }),

                // Kostenträger-Aufteilung Filter
                TernaryFilter::make('has_allocations')
                    ->label('Kostenträger-Aufteilung')
                    ->trueLabel('Mit Aufteilung')
                    ->falseLabel('Ohne Aufteilung')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('allocations'),
                        false: fn (Builder $query) => $query->whereDoesntHave('allocations'),
                    ),

                // Vollständige Aufteilung Filter
                TernaryFilter::make('complete_allocation')
                    ->label('Vollständige Aufteilung (100%)')
                    ->trueLabel('Vollständig (100%)')
                    ->falseLabel('Unvollständig (<100%)')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('allocations', function ($q) {
                            $q->havingRaw('SUM(percentage) = 100');
                        }),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereDoesntHave('allocations')
                              ->orWhereHas('allocations', function ($subQ) {
                                  $subQ->havingRaw('SUM(percentage) < 100');
                              });
                        }),
                    ),

                // Dokumente Filter
                TernaryFilter::make('has_documents')
                    ->label('Dokumente vorhanden')
                    ->trueLabel('Mit Dokumenten')
                    ->falseLabel('Ohne Dokumente')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('documents'),
                        false: fn (Builder $query) => $query->whereDoesntHave('documents'),
                    ),

                // Anbieter-Rechnungsnummer Filter
                TernaryFilter::make('has_supplier_invoice')
                    ->label('Anbieter-Rechnungsnummer')
                    ->trueLabel('Vorhanden')
                    ->falseLabel('Fehlt')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('supplier_invoice_number')
                                                           ->where('supplier_invoice_number', '!=', ''),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->whereNull('supplier_invoice_number')
                              ->orWhere('supplier_invoice_number', '');
                        }),
                    ),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm')
            )
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neue Abrechnung')
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->color('primary')
                    ->modalHeading('Neue Abrechnung erstellen')
                    ->modalWidth('4xl'),
                    
                Tables\Actions\Action::make('export')
                    ->label('Exportieren')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        // TODO: Export-Funktionalität implementieren
                        \Filament\Notifications\Notification::make()
                            ->title('Export wird vorbereitet')
                            ->body('Die Export-Funktionalität wird in Kürze verfügbar sein.')
                            ->info()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('quick_view')
                        ->label('Schnellansicht')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalContent(function (SupplierContractBilling $record) {
                            $record->load(['supplierContract.supplier.supplierType', 'allocations.solarPlant', 'documents']);
                            return view('filament.components.billing-detail-modal', ['billing' => $record]);
                        })
                        ->modalHeading(fn (SupplierContractBilling $record) => "Abrechnungsdetails - {$record->billing_number}")
                        ->modalWidth('5xl')
                        ->slideOver(),

                    Tables\Actions\ViewAction::make()
                        ->label('Vollständige Details')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (SupplierContractBilling $record): string =>
                            \App\Filament\Resources\SupplierContractBillingResource::getUrl('view', ['record' => $record])
                        )
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\EditAction::make()
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->modalWidth('4xl'),
                        
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (SupplierContractBilling $record) {
                            $newBilling = $record->replicate();
                            $newBilling->billing_number = null; // Wird automatisch generiert
                            $newBilling->status = 'draft';
                            $newBilling->billing_date = now();
                            $newBilling->due_date = null;
                            $newBilling->save();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Abrechnung dupliziert')
                                ->body("Neue Abrechnung {$newBilling->billing_number} wurde erstellt.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Abrechnung duplizieren')
                        ->modalDescription('Möchten Sie eine Kopie dieser Abrechnung erstellen?'),
                        
                    Tables\Actions\Action::make('view_allocations')
                        ->label('Kostenträger anzeigen')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->modalContent(function (SupplierContractBilling $record) {
                            $allocations = $record->allocations()->with('solarPlant')->get();
                            $totalPercentage = $allocations->sum('percentage');
                            $totalAmount = $allocations->sum('amount');
                            
                            $content = '<div class="space-y-4">';
                            $content .= '<div class="bg-gray-50 p-4 rounded-lg">';
                            $content .= '<h3 class="font-semibold text-gray-900 mb-2">Übersicht</h3>';
                            $content .= '<div class="grid grid-cols-3 gap-4 text-sm">';
                            $content .= '<div><span class="text-gray-600">Gesamtbetrag:</span><br><span class="font-semibold">€' . number_format($record->total_amount, 2, ',', '.') . '</span></div>';
                            $content .= '<div><span class="text-gray-600">Aufgeteilt:</span><br><span class="font-semibold">€' . number_format($totalAmount, 2, ',', '.') . '</span></div>';
                            $content .= '<div><span class="text-gray-600">Prozentsatz:</span><br><span class="font-semibold">' . $totalPercentage . '%</span></div>';
                            $content .= '</div></div>';
                            
                            if ($allocations->isNotEmpty()) {
                                $content .= '<div><h3 class="font-semibold text-gray-900 mb-3">Kostenträger-Aufteilung</h3>';
                                $content .= '<div class="space-y-2">';
                                foreach ($allocations as $allocation) {
                                    $plantName = $allocation->solarPlant?->name ?? 'Unbekannte Anlage';
                                    $content .= '<div class="flex justify-between items-center p-3 bg-white border rounded-lg">';
                                    $content .= '<div><span class="font-medium">' . htmlspecialchars($plantName) . '</span></div>';
                                    $content .= '<div class="text-right"><span class="text-sm text-gray-600">' . $allocation->percentage . '%</span><br><span class="font-semibold">€' . number_format($allocation->amount, 2, ',', '.') . '</span></div>';
                                    $content .= '</div>';
                                }
                                $content .= '</div></div>';
                            } else {
                                $content .= '<div class="text-center py-8 text-gray-500">Keine Kostenträger-Aufteilung vorhanden</div>';
                            }
                            
                            $content .= '</div>';
                            
                            return new \Illuminate\Support\HtmlString($content);
                        })
                        ->modalHeading(fn (SupplierContractBilling $record) => "Kostenträger - {$record->billing_number}")
                        ->modalWidth('2xl')
                        ->visible(fn (SupplierContractBilling $record) => $record->allocations()->count() > 0),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_status_update')
                        ->label('Status ändern')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Neuer Status')
                                ->options(SupplierContractBilling::getStatusOptions())
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status aktualisiert')
                                ->body(count($records) . ' Abrechnungen wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('bulk_export')
                        ->label('Ausgewählte exportieren')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // TODO: Export-Funktionalität implementieren
                            \Filament\Notifications\Notification::make()
                                ->title('Export wird vorbereitet')
                                ->body(count($records) . ' Abrechnungen werden exportiert.')
                                ->info()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('billing_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->deferLoading()
            ->poll('30s')
            ->emptyStateHeading('Keine Abrechnungen vorhanden')
            ->emptyStateDescription('Erstellen Sie die erste Abrechnung für diesen Vertrag.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
    
    public function isReadOnly(): bool
    {
        return false; // Erlaubt Aktionen auch im View-Modus
    }

    /**
     * Konfiguriert die Tabellen-Überschrift mit Statistiken
     */
    public function getTableHeading(): ?string
    {
        $contract = $this->getOwnerRecord();
        $billingsCount = $contract->billings()->count();
        $totalAmount = $contract->billings()->sum('total_amount');
        $pendingCount = $contract->billings()->where('status', 'pending')->count();
        
        $heading = static::$title;
        $heading .= " ({$billingsCount})";
        
        if ($totalAmount > 0) {
            $formattedAmount = number_format($totalAmount, 2, ',', '.') . ' €';
            $heading .= " • Gesamtvolumen: {$formattedAmount}";
        }
        
        if ($pendingCount > 0) {
            $heading .= " • {$pendingCount} ausstehend";
        }
        
        return $heading;
    }

    /**
     * Erweiterte Tabellen-Beschreibung
     */
    public function getTableDescription(): ?string
    {
        $contract = $this->getOwnerRecord();
        $latestBilling = $contract->billings()->latest('billing_date')->first();
        
        if ($latestBilling) {
            $latestDate = $latestBilling->billing_date->format('d.m.Y');
            return "Letzte Abrechnung: {$latestDate} • Sortiert nach Abrechnungsdatum (neueste zuerst)";
        }
        
        return "Alle Abrechnungen für diesen Vertrag, sortiert nach Datum";
    }

    /**
     * Angepasste Query für bessere Performance
     */
    protected function getTableQuery(): Builder
    {
        return $this->getRelationship()
            ->getQuery()
            ->with([
                'supplierContract.supplier',
                'allocations.solarPlant',
                'documents'
            ]);
    }

    /**
     * Zusätzliche Tabellen-Aktionen in der Kopfzeile
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('quick_stats')
                ->label('Statistiken')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalContent(function () {
                    $contract = $this->getOwnerRecord();
                    $billings = $contract->billings;
                    
                    $stats = [
                        'total_count' => $billings->count(),
                        'total_amount' => $billings->sum('total_amount'),
                        'avg_amount' => $billings->avg('total_amount'),
                        'status_breakdown' => $billings->groupBy('status')->map->count(),
                        'monthly_breakdown' => $billings->groupBy(function ($billing) {
                            return $billing->billing_date?->format('Y-m');
                        })->map->count()->sortKeys(),
                    ];
                    
                    $content = '<div class="space-y-6">';
                    
                    // Übersicht
                    $content .= '<div class="grid grid-cols-3 gap-4">';
                    $content .= '<div class="bg-blue-50 p-4 rounded-lg text-center">';
                    $content .= '<div class="text-2xl font-bold text-blue-600">' . $stats['total_count'] . '</div>';
                    $content .= '<div class="text-sm text-blue-800">Gesamt Abrechnungen</div>';
                    $content .= '</div>';
                    $content .= '<div class="bg-green-50 p-4 rounded-lg text-center">';
                    $content .= '<div class="text-2xl font-bold text-green-600">€' . number_format($stats['total_amount'], 0, ',', '.') . '</div>';
                    $content .= '<div class="text-sm text-green-800">Gesamtvolumen</div>';
                    $content .= '</div>';
                    $content .= '<div class="bg-purple-50 p-4 rounded-lg text-center">';
                    $content .= '<div class="text-2xl font-bold text-purple-600">€' . number_format($stats['avg_amount'], 0, ',', '.') . '</div>';
                    $content .= '<div class="text-sm text-purple-800">Ø Betrag</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    
                    // Status-Aufschlüsselung
                    if (!empty($stats['status_breakdown'])) {
                        $content .= '<div>';
                        $content .= '<h3 class="font-semibold text-gray-900 mb-3">Status-Verteilung</h3>';
                        $content .= '<div class="space-y-2">';
                        foreach ($stats['status_breakdown'] as $status => $count) {
                            $statusLabel = SupplierContractBilling::getStatusOptions()[$status] ?? $status;
                            $percentage = round(($count / $stats['total_count']) * 100, 1);
                            $content .= '<div class="flex justify-between items-center p-2 bg-gray-50 rounded">';
                            $content .= '<span>' . $statusLabel . '</span>';
                            $content .= '<span class="font-semibold">' . $count . ' (' . $percentage . '%)</span>';
                            $content .= '</div>';
                        }
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    
                    $content .= '</div>';
                    
                    return new \Illuminate\Support\HtmlString($content);
                })
                ->modalHeading('Abrechnungs-Statistiken')
                ->modalWidth('3xl'),
        ];
    }
}