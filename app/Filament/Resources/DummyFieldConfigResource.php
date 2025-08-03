<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DummyFieldConfigResource\Pages;
use App\Filament\Resources\DummyFieldConfigResource\RelationManagers;
use App\Models\DummyFieldConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DummyFieldConfigResource extends Resource
{
    protected static ?string $model = DummyFieldConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Dummy-Felder';

    protected static ?string $modelLabel = 'Dummy-Feld';

    protected static ?string $pluralModelLabel = 'Dummy-Felder';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feld-Konfiguration')
                    ->schema([
                        Forms\Components\Select::make('entity_type')
                            ->label('Entitätstyp')
                            ->options(DummyFieldConfig::getEntityTypes())
                            ->required()
                            ->default('supplier_contract')
                            ->disabled(fn ($record) => $record !== null), // Nicht editierbar bei bestehenden Einträgen
                        
                        Forms\Components\Select::make('field_key')
                            ->label('Feld')
                            ->options(DummyFieldConfig::getAvailableFieldKeys())
                            ->required()
                            ->disabled(fn ($record) => $record !== null) // Nicht editierbar bei bestehenden Einträgen
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $entityType = request()->input('data.entity_type') ?? request()->input('entity_type');
                                        if ($entityType && $value) {
                                            $exists = DummyFieldConfig::where('entity_type', $entityType)
                                                ->where('field_key', $value)
                                                ->exists();
                                            if ($exists) {
                                                $fail('Dieses Feld ist bereits für diesen Entitätstyp konfiguriert.');
                                            }
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\TextInput::make('field_label')
                            ->label('Feldbezeichnung')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Zusätzliche Informationen'),
                        
                        Forms\Components\Textarea::make('field_description')
                            ->label('Beschreibung/Hilfetext')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Optionaler Hilfetext für das Feld'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Nur aktive Felder werden in den Formularen angezeigt'),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sortierung')
                            ->numeric()
                            ->default(0)
                            ->helperText('Niedrigere Zahlen werden zuerst angezeigt'),
                        
                        Forms\Components\Select::make('column_span')
                            ->label('Spaltenbreite')
                            ->options([
                                1 => 'Halbe Breite (1 Spalte)',
                                2 => 'Volle Breite (2 Spalten)',
                            ])
                            ->default(1)
                            ->helperText('Bestimmt, ob das Feld eine halbe oder volle Breite einnimmt'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entitätstyp')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string =>
                        DummyFieldConfig::getEntityTypes()[$state] ?? $state
                    ),
                
                Tables\Columns\TextColumn::make('field_key')
                    ->label('Feld-Key')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string =>
                        DummyFieldConfig::getAvailableFieldKeys()[$state] ?? $state
                    ),
                
                Tables\Columns\TextColumn::make('field_label')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('field_description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('column_span')
                    ->label('Spaltenbreite')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state): string => match($state) {
                        1 => 'Halbe Breite',
                        2 => 'Volle Breite',
                        default => $state . ' Spalte(n)',
                    })
                    ->badge()
                    ->color(fn (int $state): string => match($state) {
                        1 => 'gray',
                        2 => 'primary',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Entitätstyp')
                    ->options(DummyFieldConfig::getEntityTypes()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
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
            ->defaultSort('entity_type')
            ->groups([
                Tables\Grouping\Group::make('entity_type')
                    ->label('Entitätstyp')
                    ->getTitleFromRecordUsing(fn (DummyFieldConfig $record): string =>
                        DummyFieldConfig::getEntityTypes()[$record->entity_type] ?? $record->entity_type
                    )
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDummyFieldConfigs::route('/'),
            'create' => Pages\CreateDummyFieldConfig::route('/create'),
            'edit' => Pages\EditDummyFieldConfig::route('/{record}/edit'),
        ];
    }
}
