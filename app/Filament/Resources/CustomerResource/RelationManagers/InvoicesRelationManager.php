<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Rechnungen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Rechnungsnummer')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Rechnungsnummer')
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
                    ->money('EUR', locale: 'de')
                    ->sortable(),
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
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->url(fn () => route('filament.admin.resources.invoices.create', [
                        'customer_id' => $this->ownerRecord->id
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.invoices.edit', $record)),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.invoices.edit', $record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}