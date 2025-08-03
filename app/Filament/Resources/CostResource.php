<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostResource\Pages;
use App\Models\Cost;
use App\Models\CostCategory;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class CostResource extends Resource
{
    protected static ?string $model = Cost::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static ?string $navigationGroup = 'Fakturierung';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Kosten';
    
    protected static ?string $modelLabel = 'Kosten';
    
    protected static ?string $pluralModelLabel = 'Kosten';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titel')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('cost_category_id')
                                    ->label('Kategorie')
                                    ->options(CostCategory::active()->ordered()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Betrag')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->inputMode('decimal'),
                                Forms\Components\DatePicker::make('date')
                                    ->label('Datum')
                                    ->required()
                                    ->default(now()),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('costable_type')
                                    ->label('Zuordnung zu')
                                    ->options([
                                        Customer::class => 'Kunde',
                                        Supplier::class => 'Lieferant',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('costable_id', null)),
                                Forms\Components\Select::make('costable_id')
                                    ->label('Kunde/Lieferant')
                                    ->options(function (callable $get) {
                                        $type = $get('costable_type');
                                        if ($type === Customer::class) {
                                            return Customer::query()
                                                ->where('is_active', true)
                                                ->orderBy('customer_number')
                                                ->get()
                                                ->pluck('name', 'id');
                                        } elseif ($type === Supplier::class) {
                                            return Supplier::query()
                                                ->where('is_active', true)
                                                ->orderBy('supplier_number')
                                                ->get()
                                                ->pluck('name', 'id');
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->visible(fn (callable $get) => filled($get('costable_type'))),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('solar_plant_id')
                                    ->label('Solaranlage')
                                    ->options(
                                        SolarPlant::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($plant) => [$plant->id => $plant->name . ' (' . $plant->plant_number . ')'])
                                    )
                                    ->searchable(),
                                Forms\Components\Select::make('project_id')
                                    ->label('Projekt')
                                    ->options(
                                        Project::query()
                                            ->whereIn('status', ['planning', 'active'])
                                            ->orderBy('created_at', 'desc')
                                            ->get()
                                            ->mapWithKeys(fn ($project) => [$project->id => $project->name . ' (' . $project->project_number . ')'])
                                    )
                                    ->searchable(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier')
                                    ->label('Lieferant (Freitext)')
                                    ->maxLength(255)
                                    ->helperText('Falls kein Lieferant im System vorhanden ist'),
                                Forms\Components\TextInput::make('reference_number')
                                    ->label('Referenznummer')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'paid' => 'Bezahlt',
                                        'cancelled' => 'Storniert',
                                    ])
                                    ->required()
                                    ->default('pending'),
                                Forms\Components\DatePicker::make('paid_at')
                                    ->label('Bezahlt am')
                                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'paid'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->color(fn ($record) => $record->category->color ?? 'gray'),
                Tables\Columns\TextColumn::make('costable.name')
                    ->label('Kunde/Lieferant')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        if ($record->costable_type === Customer::class) {
                            return '👤 ' . $record->costable?->name;
                        } elseif ($record->costable_type === Supplier::class) {
                            return '🏢 ' . $record->costable?->name;
                        }
                        return $record->supplier ?: '-';
                    }),
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projekt')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'cancelled' => 'Storniert',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referenznummer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('cost_category_id')
                    ->label('Kategorie')
                    ->options(CostCategory::active()->ordered()->pluck('name', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'paid' => 'Bezahlt',
                        'cancelled' => 'Storniert',
                    ]),
                SelectFilter::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->options(
                        SolarPlant::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn ($plant) => [$plant->id => $plant->name . ' (' . $plant->plant_number . ')'])
                    )
                    ->searchable(),
                SelectFilter::make('project_id')
                    ->label('Projekt')
                    ->options(
                        Project::query()
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->mapWithKeys(fn ($project) => [$project->id => $project->name . ' (' . $project->project_number . ')'])
                    )
                    ->searchable(),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Von Datum'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Bis Datum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
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
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListCosts::route('/'),
            'create' => Pages\CreateCost::route('/create'),
            'edit' => Pages\EditCost::route('/{record}/edit'),
        ];
    }
}
