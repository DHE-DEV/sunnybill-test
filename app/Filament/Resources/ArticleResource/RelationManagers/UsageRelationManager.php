<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\InvoiceItem;
use App\Models\SolarPlantBillingCost;
use App\Models\SolarPlantBillingCredit;

class UsageRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceItems';

    protected static ?string $title = 'Verwendung';

    protected static ?string $modelLabel = 'Verwendung';

    protected static ?string $pluralModelLabel = 'Verwendungen';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record instanceof InvoiceItem) {
                            return 'Rechnung';
                        }
                        if (class_exists(\App\Models\CreditNoteItem::class) && $record instanceof \App\Models\CreditNoteItem) {
                            return 'Gutschrift';
                        }
                        if ($record instanceof SolarPlantBillingCost) {
                            return 'Solaranlage (Kosten)';
                        }
                        if ($record instanceof SolarPlantBillingCredit) {
                            return 'Solaranlage (Gutschrift)';
                        }
                        return 'Unbekannt';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Rechnung' => 'success',
                        'Gutschrift' => 'warning',
                        'Solaranlage (Kosten)' => 'info',
                        'Solaranlage (Gutschrift)' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Belegnummer')
                    ->getStateUsing(function ($record) {
                        if ($record instanceof InvoiceItem) {
                            return $record->invoice->invoice_number ?? 'N/A';
                        }
                        if (class_exists(\App\Models\CreditNoteItem::class) && $record instanceof \App\Models\CreditNoteItem) {
                            return $record->creditNote->credit_note_number ?? 'N/A';
                        }
                        if ($record instanceof SolarPlantBillingCost) {
                            return $record->solarPlantBilling->invoice_number ?? 'N/A';
                        }
                        if ($record instanceof SolarPlantBillingCredit) {
                            return $record->solarPlantBilling->invoice_number ?? 'N/A';
                        }
                        return 'N/A';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kunde')
                    ->getStateUsing(function ($record) {
                        if ($record instanceof InvoiceItem) {
                            return $record->invoice->customer->name ?? 'N/A';
                        }
                        if (class_exists(\App\Models\CreditNoteItem::class) && $record instanceof \App\Models\CreditNoteItem) {
                            return $record->creditNote->customer->name ?? 'N/A';
                        }
                        if ($record instanceof SolarPlantBillingCost || $record instanceof SolarPlantBillingCredit) {
                            return $record->solarPlantBilling->customer->name ?? 'N/A';
                        }
                        return 'N/A';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Menge')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Einzelpreis')
                    ->money('EUR')
                    ->getStateUsing(fn ($record) => $record->unit_price ?? 0),

                Tables\Columns\TextColumn::make('total')
                    ->label('Gesamt')
                    ->money('EUR')
                    ->getStateUsing(fn ($record) => $record->total ?? 0),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'invoice' => 'Rechnung',
                        'credit_note' => 'Gutschrift',
                        'solar_cost' => 'Solaranlage (Kosten)',
                        'solar_credit' => 'Solaranlage (Gutschrift)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        // This filter won't work perfectly as we're mixing different models
                        // but we keep it for consistency
                        return $query;
                    }),
            ])
            ->headerActions([
                // Keine Create-Action, da Verwendungen automatisch durch Rechnungen etc. erstellt werden
            ])
            ->actions([
                Tables\Actions\Action::make('view_document')
                    ->label('Dokument öffnen')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function ($record) {
                        if ($record instanceof InvoiceItem) {
                            return route('filament.admin.resources.invoices.edit', $record->invoice_id);
                        }
                        if (class_exists(\App\Models\CreditNoteItem::class) && $record instanceof \App\Models\CreditNoteItem) {
                            return route('filament.admin.resources.credit-notes.edit', $record->credit_note_id);
                        }
                        if ($record instanceof SolarPlantBillingCost || $record instanceof SolarPlantBillingCredit) {
                            return route('filament.admin.resources.solar-plant-billings.edit', $record->solar_plant_billing_id);
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Keine Bulk-Actions
            ])
            ->defaultSort('created_at', 'desc');
    }
}
