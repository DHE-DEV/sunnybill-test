<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierContractResource\Pages;
use App\Filament\Resources\SupplierContractResource\RelationManagers;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SolarPlant;
use App\Models\FieldConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierContractResourceNew extends Resource
{
    protected static ?string $model = SupplierContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Verträge (Neu)';

    protected static ?string $modelLabel = 'Lieferantenvertrag';

    protected static ?string $pluralModelLabel = 'Lieferantenverträge';

    protected static ?string $navigationGroup = 'Lieferanten';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getDynamicFormSchema());
    }

    /**
     * Generiert das komplette dynamische Form Schema
     */
    protected static function getDynamicFormSchema(): array
    {
        try {
            // Hole alle aktiven Feld-Konfigurationen für supplier_contract
            $fieldConfigs = FieldConfig::forEntity('supplier_contract')
                ->active()
                ->ordered()
                ->get()
                ->groupBy('section_name');

            $sections = [];

            foreach ($fieldConfigs as $sectionName => $sectionFields) {
                $sectionSchema = [];
                
                foreach ($sectionFields as $fieldConfig) {
                    $field = static::createDynamicField($fieldConfig);
                    if ($field) {
                        $sectionSchema[] = $field;
                    }
                }

                if (!empty($sectionSchema)) {
                    $section = Forms\Components\Section::make($sectionName)
                        ->schema($sectionSchema)
                        ->columns(2);

                    // Spezielle Beschreibung für Vertragserkennung
                    if ($sectionName === 'Vertragserkennung') {
                        $section = $section->description('Diese Informationen werden zur automatischen Vertragserkennung benötigt. Es müssen nicht alle Felder befüllt werden.');
                    }

                    $sections[] = $section;
                }
            }

            return $sections;
        } catch (\Exception $e) {
            // Fallback auf statisches Schema falls FieldConfig nicht verfügbar
            return static::getFallbackSchema();
        }
    }

    /**
     * Erstellt ein dynamisches Filament Form Component
     */
    protected static function createDynamicField(FieldConfig $config): ?Forms\Components\Component
    {
        $field = null;

        switch ($config->field_type) {
            case 'text':
                $field = Forms\Components\TextInput::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['max_length'] ?? null) {
                    $field = $field->maxLength($config->field_options['max_length']);
                }
                
                if ($config->field_options['placeholder'] ?? null) {
                    $field = $field->placeholder($config->field_options['placeholder']);
                }

                if ($config->field_options['unique'] ?? false) {
                    $field = $field->unique(ignoreRecord: true);
                }
                break;

            case 'textarea':
                $field = Forms\Components\Textarea::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['rows'] ?? null) {
                    $field = $field->rows($config->field_options['rows']);
                }
                
                if ($config->field_options['max_length'] ?? null) {
                    $field = $field->maxLength($config->field_options['max_length']);
                }
                break;

            case 'select':
                $field = Forms\Components\Select::make($config->field_key)
                    ->label($config->field_label);
                
                // Spezielle Behandlung für Beziehungsfelder
                if ($config->field_key === 'supplier_id') {
                    $field = $field->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                        ->searchable()
                        ->preload();
                } elseif ($config->field_key === 'status') {
                    $field = $field->options(SupplierContract::getStatusOptions());
                } elseif ($config->field_options['options'] ?? null) {
                    $field = $field->options($config->field_options['options']);
                }
                
                if ($config->field_options['searchable'] ?? false) {
                    $field = $field->searchable();
                }
                
                if ($config->field_options['preload'] ?? false) {
                    $field = $field->preload();
                }
                
                if ($config->field_options['default'] ?? null) {
                    $field = $field->default($config->field_options['default']);
                }
                break;

            case 'date':
                $field = Forms\Components\DatePicker::make($config->field_key)
                    ->label($config->field_label);
                break;

            case 'number':
                $field = Forms\Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->numeric();
                
                if ($config->field_options['step'] ?? null) {
                    $field = $field->step($config->field_options['step']);
                }
                
                if ($config->field_options['prefix'] ?? null) {
                    $field = $field->prefix($config->field_options['prefix']);
                }
                
                if ($config->field_options['suffix'] ?? null) {
                    $field = $field->suffix($config->field_options['suffix']);
                }
                break;

            case 'toggle':
                $field = Forms\Components\Toggle::make($config->field_key)
                    ->label($config->field_label);
                
                if ($config->field_options['default'] ?? null) {
                    $field = $field->default($config->field_options['default']);
                }
                break;

            case 'email':
                $field = Forms\Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->email();
                break;

            case 'url':
                $field = Forms\Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->url();
                break;

            case 'password':
                $field = Forms\Components\TextInput::make($config->field_key)
                    ->label($config->field_label)
                    ->password();
                break;
        }

        if ($field) {
            // Gemeinsame Konfigurationen anwenden
            if ($config->field_description) {
                $field = $field->helperText($config->field_description);
            }

            if ($config->is_required) {
                $field = $field->required();
            }

            // Spaltenbreite konfigurieren
            if ($config->column_span == 2) {
                $field = $field->columnSpanFull();
            }
        }

        return $field;
    }

    /**
     * Fallback Schema falls FieldConfig nicht verfügbar
     */
    protected static function getFallbackSchema(): array
    {
        return [
            Forms\Components\Section::make('Vertragsdaten')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titel')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('Beschreibung')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('supplier_id')
                        ->label('Lieferant')
                        ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(SupplierContract::getStatusOptions())
                        ->default('draft')
                        ->required(),
                ])->columns(2),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Vertragsnummer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'expired' => 'warning',
                        'terminated' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Entwurf',
                        'active' => 'Aktiv',
                        'expired' => 'Abgelaufen',
                        'terminated' => 'Gekündigt',
                        'completed' => 'Abgeschlossen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Ende')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('formatted_contract_value')
                    ->label('Wert')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('contract_value', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Lieferant')
                    ->options(Supplier::active()->orderBy('company_name')->pluck('company_name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SupplierContract::getStatusOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
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
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SolarPlantsRelationManager::class,
            RelationManagers\BillingsRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierContracts::route('/'),
            'create' => Pages\CreateSupplierContract::route('/create'),
            'view' => Pages\ViewSupplierContract::route('/{record}'),
            'edit' => Pages\EditSupplierContract::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // Zugriffskontrolle für Superadmin-Team
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()?->teams()->where('name', 'Superadmin')->exists() ?? false;
    }
}