<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use App\Models\SupplierType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Kontakte';

    protected static ?string $modelLabel = 'Lieferanten';

    protected static ?string $pluralModelLabel = 'Lieferanten - Kontakte';

    protected static ?string $navigationGroup = 'Lieferanten';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Firmendaten')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firmenname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('supplier_type_id')
                            ->label('Lieferantentyp')
                            ->options(SupplierType::active()->ordered()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('ranking')
                            ->label('Ranking')
                            ->options([
                                'A' => 'A Lieferant',
                                'B' => 'B Lieferant',
                                'C' => 'C Lieferant',
                                'D' => 'D Lieferant',
                                'E' => 'E Lieferant',
                            ])
                            ->placeholder('Ranking auswählen')
                            ->helperText('Klassifizierung der Lieferantenwichtigkeit'),
                        Forms\Components\TextInput::make('creditor_number')
                            ->label('Eigene Kundennummer bei Lieferant')
                            ->maxLength(255)
                            ->placeholder('z.B. 12345'),
                        Forms\Components\TextInput::make('contract_number')
                            ->label('Eigene Vertragsnummer bei Lieferant')
                            ->maxLength(255)
                            ->placeholder('z.B. V-2024-001'),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Ansprechpartner')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('z.B. https://www.beispiel.de'),
                    ])->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(3)
                            ->placeholder('Straße & Hausnummer, PLZ Ort'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('PLZ')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('city')
                            ->label('Stadt')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->label('Land')
                            ->default('Deutschland')
                            ->maxLength(255),
                    ])->columns(2),

                /*    
                Forms\Components\Section::make('Vertragserkennung')
                    ->description('Diese Informationen werden zur automatischen Vertragserkennung benötigt. Es müssen nicht alle Felder befüllt werden.')
                    ->schema([
                        Forms\Components\TextInput::make('contract_recognition_1')
                            ->label('Vertragserkennung 1')
                            ->maxLength(255)
                            ->placeholder('z.B. Erkennungsmerkmal 1'),
                        Forms\Components\TextInput::make('contract_recognition_2')
                            ->label('Vertragserkennung 2')
                            ->maxLength(255)
                            ->placeholder('z.B. Erkennungsmerkmal 2'),
                        Forms\Components\TextInput::make('contract_recognition_3')
                            ->label('Vertragserkennung 3')
                            ->maxLength(255)
                            ->placeholder('z.B. Erkennungsmerkmal 3'),
                    ])->columns(2),
*/
                Forms\Components\Section::make('Steuerliche Daten')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Steuernummer')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vat_id')
                            ->label('Umsatzsteuer-ID')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Lexoffice-Synchronisation')
                    ->schema([
                        Forms\Components\TextInput::make('lexoffice_id')
                            ->label('Lexoffice-ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('lexoffice_synced_at')
                            ->label('Zuletzt synchronisiert')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2)
                    ->visible(fn ($record) => $record?->lexoffice_id),

                Forms\Components\Section::make('Zusätzliche Felder')
                    ->schema(\App\Models\DummyFieldConfig::getDummyFieldsSchema('supplier'))
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Sonstiges')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Firmendaten')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('supplier_number')
                            ->label('Lieferanten-Nr.'),
                        \Filament\Infolists\Components\TextEntry::make('company_name')
                            ->label('Firmenname'),
                        \Filament\Infolists\Components\TextEntry::make('supplierType.name')
                            ->label('Lieferantentyp'),
                        \Filament\Infolists\Components\TextEntry::make('ranking')
                            ->label('Ranking')
                            ->formatStateUsing(fn (?string $state): string => $state ? $state . ' Lieferant' : '-')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'A' => 'success',
                                'B' => 'info',
                                'C' => 'warning',
                                'D' => 'danger',
                                'E' => 'gray',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('creditor_number')
                            ->label('Eigene Kundennummer bei Lieferant'),
                        \Filament\Infolists\Components\TextEntry::make('contract_number')
                            ->label('Eigene Vertragsnummer bei Lieferant'),
                        \Filament\Infolists\Components\TextEntry::make('contact_person')
                            ->label('Ansprechpartner'),
                        \Filament\Infolists\Components\TextEntry::make('email')
                            ->label('E-Mail')
                            ->copyable()
                            ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                            ->openUrlInNewTab(false),
                        \Filament\Infolists\Components\TextEntry::make('website')
                            ->label('Website')
                            ->url(fn ($record) => $record->website)
                            ->openUrlInNewTab(),
                    ])->columns(2),
                \Filament\Infolists\Components\Section::make('Adresse')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('address')
                            ->label('Adresse')
                            ->prose(),
                        \Filament\Infolists\Components\TextEntry::make('postal_code')
                            ->label('PLZ'),
                        \Filament\Infolists\Components\TextEntry::make('city')
                            ->label('Stadt'),
                        \Filament\Infolists\Components\TextEntry::make('country')
                            ->label('Land'),
                    ])->columns(2),
                \Filament\Infolists\Components\Section::make('Steuerliche Daten')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('tax_number')
                            ->label('Steuernummer'),
                        \Filament\Infolists\Components\TextEntry::make('vat_id')
                            ->label('Umsatzsteuer-ID'),
                    ])->columns(2),
                \Filament\Infolists\Components\Section::make('Status & Sonstiges')
                    ->schema([
                        \Filament\Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label('Notizen')
                            ->prose()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_number')
                    ->label('Lieferanten-Nr.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creditor_number')
                    ->label('Eigene Kundennummer bei Lieferant')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Eigene Vertragsnummer bei Lieferant')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firmenname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplierType.name')
                    ->label('Typ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Ranking')
                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' Lieferant' : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D' => 'danger',
                        'E' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\IconColumn::make('lexoffice_synced')
                    ->label('Lexoffice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSyncedWithLexoffice())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Mitarbeiter')
                    ->counts('employees')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_type_id')
                    ->label('Lieferantentyp')
                    ->options(SupplierType::active()->ordered()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
                ]),
            ])
            ->defaultSort('supplier_number', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager::class,
            \App\Filament\Resources\CustomerResource\RelationManagers\PhoneNumbersRelationManager::class,
            RelationManagers\EmployeesRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\ArticlesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
            RelationManagers\SolarPlantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
