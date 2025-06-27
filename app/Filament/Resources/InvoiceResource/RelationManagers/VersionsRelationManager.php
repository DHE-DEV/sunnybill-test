<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versionshistorie';

    protected static ?string $modelLabel = 'Version';

    protected static ?string $pluralModelLabel = 'Versionen';

    // JavaScript-basierte Aktualisierung wird verwendet

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version_number')
                    ->label('Version')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->sortable()
                    ->badge(),
                
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
                    ->label('Geändert von'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('current')
                    ->label('Aktuelle Version')
                    ->query(fn (Builder $query): Builder => $query->where('is_current', true)),
            ])
            ->headerActions([
                // Versionen werden automatisch erstellt, daher keine Create-Action
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('create_copy')
                    ->label('Kopie erstellen')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function ($record) {
                        $newInvoice = $record->createInvoiceCopy();
                        
                        return redirect()->route('filament.admin.resources.invoices.edit', $newInvoice);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Rechnungskopie erstellen')
                    ->modalDescription('Möchten Sie eine neue Rechnung basierend auf dieser Version erstellen?'),
                
                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('invoice.pdf.version', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // Keine Bulk-Actions für Versionen
            ])
            ->defaultSort('version_number', 'desc')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }
}