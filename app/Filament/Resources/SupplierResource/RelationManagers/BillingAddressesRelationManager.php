<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Rechnungsadressen';

    protected static ?string $modelLabel = 'Rechnungsadresse';

    protected static ?string $pluralModelLabel = 'Rechnungsadressen';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function modifyQuery(Builder $query): Builder
    {
        return $query->where('type', 'billing');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rechnungsadresse')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firmenname')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Ansprechpartner')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('street_address')
                            ->label('Straße & Hausnummer')
                            ->required()
                            ->rows(2),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('PLZ')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('city')
                            ->label('Stadt')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->label('Bundesland/Region')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->label('Land')
                            ->default('Deutschland')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('label')
                            ->label('Beschreibung')
                            ->maxLength(255)
                            ->placeholder('z.B. Buchhaltung, Zentrale, etc.'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Haupt-Rechnungsadresse')
                            ->helperText('Nur eine Rechnungsadresse kann als Hauptadresse markiert werden.')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street_address')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('street_address')
                    ->label('Straße')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('short_address')
                    ->label('PLZ/Ort')
                    ->searchable(['postal_code', 'city'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Land')
                    ->toggleable()
                    ->placeholder('Deutschland'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Beschreibung')
                    ->toggleable()
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Hauptadresse')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-document-text')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Haupt-Rechnungsadresse'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Rechnungsadresse hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['addressable_type'] = $this->getOwnerRecord()::class;
                        $data['addressable_id'] = $this->getOwnerRecord()->id;
                        $data['type'] = 'billing';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_primary', 'desc')
            ->emptyStateHeading('Keine Rechnungsadressen vorhanden')
            ->emptyStateDescription('Fügen Sie die erste Rechnungsadresse für diesen Lieferanten hinzu.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}