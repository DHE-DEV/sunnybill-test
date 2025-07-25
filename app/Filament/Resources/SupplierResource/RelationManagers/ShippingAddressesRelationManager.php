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

class ShippingAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Lieferadressen';

    protected static ?string $modelLabel = 'Lieferadresse';

    protected static ?string $pluralModelLabel = 'Lieferadressen';

    protected static ?string $icon = 'heroicon-o-truck';

    public function modifyQuery(Builder $query): Builder
    {
        return $query->where('type', 'shipping');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lieferadresse')
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
                            ->placeholder('z.B. Lager, Baustelle, etc.'),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Haupt-Lieferadresse')
                            ->helperText('Nur eine Lieferadresse kann als Hauptadresse markiert werden.')
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
                    ->falseIcon('heroicon-o-truck')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Haupt-Lieferadresse'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Lieferadresse hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['addressable_type'] = $this->getOwnerRecord()::class;
                        $data['addressable_id'] = $this->getOwnerRecord()->id;
                        $data['type'] = 'shipping';
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
            ->emptyStateHeading('Keine Lieferadressen vorhanden')
            ->emptyStateDescription('Fügen Sie die erste Lieferadresse für diesen Lieferanten hinzu.')
            ->emptyStateIcon('heroicon-o-truck');
    }
}