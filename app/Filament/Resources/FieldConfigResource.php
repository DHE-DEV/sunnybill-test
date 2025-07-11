<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldConfigResource\Pages;
use App\Models\FieldConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FieldConfigResource extends Resource
{
    protected static ?string $model = FieldConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Feld-Konfiguration';

    protected static ?string $modelLabel = 'Feld-Konfiguration';

    protected static ?string $pluralModelLabel = 'Feld-Konfigurationen';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        Forms\Components\Select::make('entity_type')
                            ->label('Entitätstyp')
                            ->options(FieldConfig::getEntityTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Reset section_name when entity_type changes
                                $set('section_name', null);
                            }),

                        Forms\Components\TextInput::make('field_key')
                            ->label('Feld-Schlüssel')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Eindeutiger Bezeichner für das Feld (z.B. title, description)')
                            ->rules(['regex:/^[a-z_]+$/'])
                            ->placeholder('z.B. custom_field_1'),

                        Forms\Components\TextInput::make('field_label')
                            ->label('Feld-Bezeichnung')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Zusatzfeld 1'),

                        Forms\Components\Textarea::make('field_description')
                            ->label('Hilfetext')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Optionaler Hilfetext für das Feld'),
                    ])->columns(2),

                Forms\Components\Section::make('Feld-Konfiguration')
                    ->schema([
                        Forms\Components\Select::make('field_type')
                            ->label('Feld-Typ')
                            ->options(FieldConfig::getFieldTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Reset field_options when field_type changes
                                $set('field_options', []);
                            }),

                        Forms\Components\KeyValue::make('field_options')
                            ->label('Feld-Optionen')
                            ->keyLabel('Option')
                            ->valueLabel('Wert')
                            ->helperText('JSON-Konfiguration für das Feld (z.B. max_length, placeholder, options)')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_required')
                            ->label('Pflichtfeld')
                            ->default(false),

                        Forms\Components\Toggle::make('is_system_field')
                            ->label('System-Feld')
                            ->default(false)
                            ->helperText('System-Felder sind vordefinierte Felder der Anwendung'),
                    ])->columns(2),

                Forms\Components\Section::make('Layout & Anordnung')
                    ->schema([
                        Forms\Components\Select::make('section_name')
                            ->label('Sektion')
                            ->options(function (callable $get) {
                                $entityType = $get('entity_type');
                                if (!$entityType) {
                                    return [];
                                }
                                return FieldConfig::getSectionsForEntity($entityType);
                            })
                            ->required()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('section_name')
                                    ->label('Neue Sektion')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return $data['section_name'];
                            }),

                        Forms\Components\TextInput::make('section_sort_order')
                            ->label('Sektions-Reihenfolge')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Reihenfolge der Sektionen (1 = erste Sektion)'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Feld-Reihenfolge')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Reihenfolge innerhalb der Sektion'),

                        Forms\Components\Select::make('column_span')
                            ->label('Spaltenbreite')
                            ->options([
                                1 => 'Halbe Breite (1 Spalte)',
                                2 => 'Volle Breite (2 Spalten)',
                            ])
                            ->default(1)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entität')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FieldConfig::getEntityTypes()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'supplier_contract' => 'primary',
                        'customer' => 'success',
                        'supplier' => 'warning',
                        'solar_plant' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('section_name')
                    ->label('Sektion')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('field_key')
                    ->label('Feld-Schlüssel')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Feld-Schlüssel kopiert!')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('field_label')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('field_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FieldConfig::getFieldTypes()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'textarea' => 'blue',
                        'select' => 'green',
                        'date' => 'yellow',
                        'number' => 'orange',
                        'toggle' => 'purple',
                        'email' => 'pink',
                        'url' => 'indigo',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Reihenfolge')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('column_span')
                    ->label('Breite')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Halbe Breite',
                        2 => 'Volle Breite',
                        default => (string) $state,
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'gray',
                        2 => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Pflicht')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_system_field')
                    ->label('System')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Entitätstyp')
                    ->options(FieldConfig::getEntityTypes()),

                Tables\Filters\SelectFilter::make('field_type')
                    ->label('Feld-Typ')
                    ->options(FieldConfig::getFieldTypes()),

                Tables\Filters\SelectFilter::make('section_name')
                    ->label('Sektion')
                    ->options(function () {
                        return FieldConfig::distinct('section_name')
                            ->whereNotNull('section_name')
                            ->pluck('section_name', 'section_name')
                            ->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_system_field')
                    ->label('System-Feld'),

                Tables\Filters\TernaryFilter::make('is_required')
                    ->label('Pflichtfeld'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Feld-Konfiguration duplizieren')
                        ->modalDescription('Möchten Sie diese Feld-Konfiguration duplizieren?')
                        ->modalSubmitActionLabel('Ja, duplizieren')
                        ->action(function (FieldConfig $record) {
                            $newRecord = $record->replicate();
                            $newRecord->field_key = $record->field_key . '_copy';
                            $newRecord->field_label = $record->field_label . ' (Kopie)';
                            $newRecord->is_active = false; // Deaktiviert, um Konflikte zu vermeiden
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Feld-Konfiguration dupliziert')
                                ->body("Die Konfiguration wurde als '{$newRecord->field_key}' dupliziert.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Feld-Konfiguration löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie diese Feld-Konfiguration löschen möchten? Dies kann die Anwendung beeinträchtigen.')
                        ->modalSubmitActionLabel('Ja, löschen'),
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
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('entity_type')
            ->defaultGroup('entity_type')
            ->groups([
                Tables\Grouping\Group::make('entity_type')
                    ->label('Entitätstyp')
                    ->getTitleFromRecordUsing(fn (FieldConfig $record): string => FieldConfig::getEntityTypes()[$record->entity_type] ?? $record->entity_type),
                Tables\Grouping\Group::make('section_name')
                    ->label('Sektion'),
                Tables\Grouping\Group::make('field_type')
                    ->label('Feld-Typ')
                    ->getTitleFromRecordUsing(fn (FieldConfig $record): string => FieldConfig::getFieldTypes()[$record->field_type] ?? $record->field_type),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFieldConfigs::route('/'),
            'create' => Pages\CreateFieldConfig::route('/create'),
            'view' => Pages\ViewFieldConfig::route('/{record}'),
            'edit' => Pages\EditFieldConfig::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}