<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Projekte';

    protected static ?string $navigationGroup = 'Projektverwaltung';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Projekt-Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Projektname')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('project_number')
                                    ->label('Projekt-Nr.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Projekttyp')
                                    ->options([
                                        'solar_plant' => 'Solaranlage',
                                        'internal' => 'Intern',
                                        'customer' => 'Kundenprojekt',
                                        'development' => 'Entwicklung',
                                        'maintenance' => 'Wartung',
                                    ])
                                    ->required()
                                    ->native(false),
                                Forms\Components\Select::make('priority')
                                    ->label('Priorität')
                                    ->options([
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('medium'),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'planning' => 'Planung',
                                        'active' => 'Aktiv',
                                        'on_hold' => 'Pausiert',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('planning'),
                            ]),
                    ]),

                Forms\Components\Section::make('Zeitplan & Budget')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Startdatum')
                                    ->native(false),
                                Forms\Components\DatePicker::make('planned_end_date')
                                    ->label('Geplantes Enddatum')
                                    ->native(false),
                                Forms\Components\DatePicker::make('actual_end_date')
                                    ->label('Tatsächliches Enddatum')
                                    ->native(false),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('budget')
                                    ->label('Budget')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('actual_costs')
                                    ->label('Tatsächliche Kosten')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('progress_percentage')
                                    ->label('Fortschritt (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->default(0),
                            ]),
                    ]),

                Forms\Components\Section::make('Zuordnungen')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Kunde')
                                    ->options(fn () => \App\Models\Customer::whereNotNull('name')->pluck('name', 'id'))
                                    ->searchable(),
                                Forms\Components\Select::make('supplier_id')
                                    ->label('Lieferant')
                                    ->options(fn () => \App\Models\Supplier::whereNotNull('name')->pluck('name', 'id'))
                                    ->searchable(),
                                Forms\Components\Select::make('solar_plant_id')
                                    ->label('Solaranlage')
                                    ->options(fn () => \App\Models\SolarPlant::whereNotNull('name')->pluck('name', 'id'))
                                    ->searchable(),
                                Forms\Components\Select::make('project_manager_id')
                                    ->label('Projektleiter')
                                    ->options(fn () => \App\Models\User::whereNotNull('name')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                            ]),
                    ]),

                Forms\Components\Section::make('Erweiterte Optionen')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_number')
                    ->label('Projekt-Nr.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Projektname')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Fortschritt')
                    ->suffix('%')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('planned_end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('projectManager.name')
                    ->label('Projektleiter')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ]),
                Tables\Filters\SelectFilter::make('project_manager_id')
                    ->label('Projektleiter')
                    ->relationship('projectManager', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_end_date', '<', now())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Projekt-Informationen')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('project_number')
                                    ->label('Projekt-Nr.'),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Projektname'),
                                Infolists\Components\TextEntry::make('type')
                                    ->label('Projekttyp')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'solar_plant' => 'Solaranlage',
                                        'internal' => 'Intern',
                                        'customer' => 'Kundenprojekt',
                                        'development' => 'Entwicklung',
                                        'maintenance' => 'Wartung',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'planning' => 'gray',
                                        'active' => 'success',
                                        'on_hold' => 'warning',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'planning' => 'Planung',
                                        'active' => 'Aktiv',
                                        'on_hold' => 'Pausiert',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'low' => 'gray',
                                        'medium' => 'info',
                                        'high' => 'warning',
                                        'urgent' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('Fortschritt')
                                    ->suffix('%'),
                            ]),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Zeitplan & Budget')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('Startdatum')
                                    ->date('d.m.Y'),
                                Infolists\Components\TextEntry::make('planned_end_date')
                                    ->label('Geplantes Enddatum')
                                    ->date('d.m.Y'),
                                Infolists\Components\TextEntry::make('actual_end_date')
                                    ->label('Tatsächliches Enddatum')
                                    ->date('d.m.Y'),
                                Infolists\Components\TextEntry::make('budget')
                                    ->label('Budget')
                                    ->money('EUR'),
                                Infolists\Components\TextEntry::make('actual_costs')
                                    ->label('Tatsächliche Kosten')
                                    ->money('EUR'),
                                Infolists\Components\TextEntry::make('days_remaining')
                                    ->label('Verbleibende Tage')
                                    ->getStateUsing(fn (Project $record): string => 
                                        $record->days_remaining !== null 
                                            ? ($record->days_remaining >= 0 ? $record->days_remaining . ' Tage' : 'Überfällig') 
                                            : 'Kein Enddatum'
                                    ),
                            ]),
                    ]),

                Infolists\Components\Section::make('Zuordnungen')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Kunde'),
                                Infolists\Components\TextEntry::make('supplier.name')
                                    ->label('Lieferant'),
                                Infolists\Components\TextEntry::make('solarPlant.name')
                                    ->label('Solaranlage'),
                                Infolists\Components\TextEntry::make('projectManager.name')
                                    ->label('Projektleiter'),
                            ]),
                    ]),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['planning', 'active'])->count();
    }
}
