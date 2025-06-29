<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupplierEmployee;

class SuppliersRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierAssignments';

    protected static ?string $title = 'zugeordnete Firmen';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->supplierAssignments()->count();
    }

    protected static ?string $modelLabel = 'Zuordnung';

    protected static ?string $pluralModelLabel = 'Zuordnungen';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lieferant & Ansprechpartner')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Lieferant')
                            ->options(function () {
                                // Alle aktiven Lieferanten anzeigen (Mehrfachzuordnung erlauben)
                                return \App\Models\Supplier::where('is_active', true)
                                    ->orderBy('company_name')
                                    ->pluck('company_name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->preload()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('supplier_employee_id', null)),
                        Forms\Components\Select::make('supplier_employee_id')
                            ->label('Ansprechpartner')
                            ->options(function (Forms\Get $get) {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) return [];
                                
                                return SupplierEmployee::where('supplier_id', $supplierId)
                                    ->where('is_active', true)
                                    ->orderBy('first_name')
                                    ->get()
                                    ->pluck('display_name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->helperText('Wählen Sie zuerst einen Lieferanten aus'),
                        Forms\Components\TextInput::make('role')
                            ->label('Rolle/Aufgabe')
                            ->placeholder('z.B. Installateur, Wartung, Komponenten, Planung')
                            ->maxLength(255)
                            ->datalist([
                                'Installateur',
                                'Wartung',
                                'Komponenten',
                                'Planung',
                                'Support',
                                'Beratung',
                                'Projektleitung',
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Zeitraum')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Beginn der Zusammenarbeit')
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Ende der Zusammenarbeit')
                            ->after('start_date'),
                    ])->columns(2),

                Forms\Components\Section::make('Anlagen-spezifische Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Besondere Vereinbarungen für diese Solaranlage')
                            ->rows(4)
                            ->placeholder('Wartungsintervalle, spezielle Anforderungen, Kontaktzeiten, etc.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktive Zuordnung')
                            ->default(true)
                            ->helperText('Deaktivieren, wenn die Zusammenarbeit beendet ist'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('supplier.company_name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['supplier.phoneNumbers', 'supplierEmployee.phoneNumbers']))
            ->columns([
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rolle')
                    ->searchable()
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Installateur' => 'success',
                        'Wartung' => 'warning',
                        'Komponenten' => 'info',
                        'Planung' => 'primary',
                        'Support' => 'danger',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('supplierEmployee.full_name')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('supplierEmployee.primary_phone')
                    ->label('Telefon')
                    ->toggleable()
                    ->placeholder('-')
                    ->url(fn ($state) => $state ? 'tel:' . preg_replace('/[^+\d]/', '', $state) : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Beginn')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ende')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notizen')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktive Zuordnungen'),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rolle')
                    ->options([
                        'Installateur' => 'Installateur',
                        'Wartung' => 'Wartung',
                        'Komponenten' => 'Komponenten',
                        'Planung' => 'Planung',
                        'Support' => 'Support',
                        'Beratung' => 'Beratung',
                        'Projektleitung' => 'Projektleitung',
                    ]),
                Tables\Filters\Filter::make('current_assignments')
                    ->label('Laufende Zuordnungen')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)
                              ->where(function ($q) {
                                  $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now());
                              })
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Lieferant zuordnen'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalContentFooter(null)
                        ->extraModalWindowAttributes(['class' => 'custom-modal-bg'])
                        ->infolist([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    // Linke Spalte - Lieferant
                                    Infolists\Components\Group::make([
                                        Infolists\Components\Section::make()
                                            ->heading('Lieferant')
                                            ->description('Grundlegende Unternehmensinformationen')
                                            ->icon('heroicon-o-building-office-2')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('supplier.company_name')
                                                    ->label('Firmenname')
                                                    ->size('lg')
                                                    ->weight('bold')
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('supplier.contact_person')
                                                    ->label('Hauptansprechpartner')
                                                    ->icon('heroicon-o-user'),
                                                Infolists\Components\TextEntry::make('supplier.email')
                                                    ->label('E-Mail')
                                                    ->icon('heroicon-o-envelope')
                                                    ->copyable()
                                                    ->color('info'),
                                                Infolists\Components\TextEntry::make('supplier.website')
                                                    ->label('Website')
                                                    ->icon('heroicon-o-globe-alt')
                                                    ->url(fn ($record) => $record->supplier?->website)
                                                    ->openUrlInNewTab()
                                                    ->color('success'),
                                            ])->compact(),
                                        
                                        Infolists\Components\Section::make()
                                            ->heading('Adresse & Steuer')
                                            ->icon('heroicon-o-map-pin')
                                            ->schema([
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('supplier.address')
                                                            ->label('Straße'),
                                                        Infolists\Components\TextEntry::make('supplier.postal_code')
                                                            ->label('PLZ'),
                                                        Infolists\Components\TextEntry::make('supplier.city')
                                                            ->label('Stadt'),
                                                        Infolists\Components\TextEntry::make('supplier.country')
                                                            ->label('Land'),
                                                    ]),
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('supplier.tax_number')
                                                            ->label('Steuernummer')
                                                            ->icon('heroicon-o-document-text'),
                                                        Infolists\Components\TextEntry::make('supplier.vat_id')
                                                            ->label('USt-IdNr.')
                                                            ->icon('heroicon-o-identification'),
                                                    ]),
                                            ])->compact(),
                                        
                                        Infolists\Components\Section::make()
                                            ->heading('Unternehmen Telefonnummern')
                                            ->icon('heroicon-o-phone')
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('supplier.phoneNumbers')
                                                    ->label('')
                                                    ->schema([
                                                        Infolists\Components\Grid::make(3)
                                                            ->schema([
                                                                Infolists\Components\TextEntry::make('phone_number')
                                                                    ->label('Nummer')
                                                                    ->copyable()
                                                                    ->weight('bold')
                                                                    ->color('primary')
                                                                    ->url(fn ($state) => 'tel:' . preg_replace('/[^+\d]/', '', $state))
                                                                    ->openUrlInNewTab(false),
                                                                Infolists\Components\TextEntry::make('type')
                                                                    ->label('Typ')
                                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                                        'business' => 'Geschäftlich',
                                                                        'mobile' => 'Mobil',
                                                                        'private' => 'Privat',
                                                                        default => $state
                                                                    })
                                                                    ->badge()
                                                                    ->color(fn ($state) => match($state) {
                                                                        'business' => 'success',
                                                                        'mobile' => 'warning',
                                                                        'private' => 'gray',
                                                                        default => 'gray'
                                                                    }),
                                                                Infolists\Components\TextEntry::make('label')
                                                                    ->label('Bezeichnung')
                                                                    ->color('gray'),
                                                            ]),
                                                        Infolists\Components\IconEntry::make('is_primary')
                                                            ->label('Hauptnummer')
                                                            ->boolean()
                                                            ->trueIcon('heroicon-o-star')
                                                            ->falseIcon('heroicon-o-phone')
                                                            ->trueColor('warning')
                                                            ->falseColor('gray'),
                                                    ])
                                                    ->contained(false),
                                            ])->compact()
                                            ->visible(fn ($record) => $record->supplier !== null && $record->supplier->phoneNumbers->count() > 0),
                                    ])->columnSpan(1),
                                    
                                    // Rechte Spalte - Mitarbeiter & Zuordnung
                                    Infolists\Components\Group::make([
                                        Infolists\Components\Section::make()
                                            ->heading('Ansprechpartner')
                                            ->description('Zugeordneter Mitarbeiter für diese Anlage')
                                            ->icon('heroicon-o-user-circle')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('supplierEmployee.full_name')
                                                    ->label('Name')
                                                    ->size('lg')
                                                    ->weight('bold')
                                                    ->color('primary'),
                                                Infolists\Components\TextEntry::make('supplierEmployee.position')
                                                    ->label('Position')
                                                    ->badge()
                                                    ->color('gray'),
                                                Infolists\Components\TextEntry::make('supplierEmployee.email')
                                                    ->label('E-Mail')
                                                    ->icon('heroicon-o-envelope')
                                                    ->copyable()
                                                    ->color('info'),
                                                Infolists\Components\IconEntry::make('supplierEmployee.is_primary_contact')
                                                    ->label('Hauptansprechpartner des Unternehmens')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-star')
                                                    ->falseIcon('heroicon-o-user')
                                                    ->trueColor('warning')
                                                    ->falseColor('gray'),
                                            ])->compact()
                                            ->visible(fn ($record) => $record->supplierEmployee !== null),
                                        
                                        Infolists\Components\Section::make()
                                            ->heading('Mitarbeiter Telefonnummern')
                                            ->icon('heroicon-o-phone')
                                            ->schema([
                                                Infolists\Components\RepeatableEntry::make('supplierEmployee.phoneNumbers')
                                                    ->label('')
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('phoneable.full_name')
                                                            ->label('Mitarbeiter')
                                                            ->size('sm')
                                                            ->weight('bold')
                                                            ->color('primary')
                                                            ->formatStateUsing(function ($state, $record) {
                                                                // Da wir über supplierEmployee.phoneNumbers iterieren,
                                                                // können wir den Namen direkt aus der Relation holen
                                                                return $record->phoneable->full_name ?? 'Unbekannt';
                                                            }),
                                                        Infolists\Components\Grid::make(3)
                                                            ->schema([
                                                                Infolists\Components\TextEntry::make('phone_number')
                                                                    ->label('Nummer')
                                                                    ->copyable()
                                                                    ->weight('bold')
                                                                    ->color('primary')
                                                                    ->url(fn ($state) => 'tel:' . preg_replace('/[^+\d]/', '', $state))
                                                                    ->openUrlInNewTab(false),
                                                                Infolists\Components\TextEntry::make('type')
                                                                    ->label('Typ')
                                                                    ->formatStateUsing(fn ($state) => match($state) {
                                                                        'business' => 'Geschäftlich',
                                                                        'mobile' => 'Mobil',
                                                                        'private' => 'Privat',
                                                                        default => $state
                                                                    })
                                                                    ->badge()
                                                                    ->color(fn ($state) => match($state) {
                                                                        'business' => 'success',
                                                                        'mobile' => 'warning',
                                                                        'private' => 'gray',
                                                                        default => 'gray'
                                                                    }),
                                                                Infolists\Components\TextEntry::make('label')
                                                                    ->label('Bezeichnung')
                                                                    ->color('gray'),
                                                            ]),
                                                        Infolists\Components\IconEntry::make('is_primary')
                                                            ->label('Hauptnummer')
                                                            ->boolean()
                                                            ->trueIcon('heroicon-o-star')
                                                            ->falseIcon('heroicon-o-phone')
                                                            ->trueColor('warning')
                                                            ->falseColor('gray'),
                                                    ])
                                                    ->contained(false),
                                            ])->compact()
                                            ->visible(fn ($record) => $record->supplierEmployee !== null && $record->supplierEmployee->phoneNumbers->count() > 0),
                                    ])->columnSpan(1),
                                ]),
                            
                            // Vollbreite Sektion für Zuordnungsdetails
                            Infolists\Components\Section::make()
                                ->heading('Zuordnungsdetails')
                                ->description('Details zur Zusammenarbeit bei dieser Solaranlage')
                                ->icon('heroicon-o-link')
                                ->schema([
                                    Infolists\Components\Grid::make(4)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('role')
                                                ->label('Rolle')
                                                ->badge()
                                                ->size('lg')
                                                ->color(fn ($state) => match($state) {
                                                    'Installateur' => 'success',
                                                    'Wartung' => 'warning',
                                                    'Komponenten' => 'info',
                                                    'Planung' => 'primary',
                                                    'Support' => 'danger',
                                                    default => 'gray'
                                                }),
                                            Infolists\Components\TextEntry::make('start_date')
                                                ->label('Beginn')
                                                ->date('d.m.Y')
                                                ->icon('heroicon-o-calendar'),
                                            Infolists\Components\TextEntry::make('end_date')
                                                ->label('Ende')
                                                ->date('d.m.Y')
                                                ->placeholder('Laufend')
                                                ->icon('heroicon-o-calendar'),
                                            Infolists\Components\IconEntry::make('is_active')
                                                ->label('Status')
                                                ->boolean()
                                                ->trueIcon('heroicon-o-check-circle')
                                                ->falseIcon('heroicon-o-x-circle')
                                                ->trueColor('success')
                                                ->falseColor('danger'),
                                        ]),
                                    Infolists\Components\TextEntry::make('notes')
                                        ->label('Anlagen-spezifische Notizen')
                                        ->placeholder('Keine besonderen Notizen für diese Anlage')
                                        ->columnSpanFull()
                                        ->prose()
                                        ->markdown(),
                                ])->compact(),
                        ])
                        ->modalWidth('6xl'),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-o-cog-6-tooth')
                ->tooltip('Aktionen')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('supplier.company_name')
            ->emptyStateHeading('Keine Lieferanten zugeordnet')
            ->emptyStateDescription('Ordnen Sie Lieferanten und Dienstleister dieser Solaranlage zu.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}