<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolarPlantStatusResource\Pages;
use App\Models\SolarPlantStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class SolarPlantStatusResource extends Resource
{
    protected static ?string $model = SolarPlantStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Solaranlagen-Status';

    protected static ?string $modelLabel = 'Status';

    protected static ?string $pluralModelLabel = 'Status';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status-Informationen')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Schlüssel')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('z.B. in_planning')
                                    ->helperText('Eindeutiger Schlüssel für den Status (nur Kleinbuchstaben, Zahlen und Unterstriche)')
                                    ->rules(['regex:/^[a-z0-9_]+$/']),
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. In Planung'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Beschreibung des Status...')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Darstellung')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('color')
                                    ->label('Farbe')
                                    ->options(SolarPlantStatus::getColorOptions())
                                    ->default('gray')
                                    ->required(),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sortierreihenfolge')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->helperText('Niedrigere Zahlen werden zuerst angezeigt'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->helperText('Nur aktive Status sind in Auswahlfeldern verfügbar'),
                            ]),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Standard-Status')
                            ->helperText('Dieser Status wird für neue Solaranlagen verwendet')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50)
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('key')
                    ->label('Schlüssel')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\BadgeColumn::make('color')
                    ->label('Farbe')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => $state),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Standard')
                    ->boolean(),
                Tables\Columns\TextColumn::make('solarPlants_count')
                    ->label('Anlagen')
                    ->counts('solarPlants')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Standard'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('set_default')
                        ->label('Als Standard setzen')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn (SolarPlantStatus $record) => !$record->is_default)
                        ->action(function (SolarPlantStatus $record) {
                            SolarPlantStatus::setDefault($record->key);
                            Notification::make()
                                ->title('Standard-Status geändert')
                                ->body("'{$record->name}' ist jetzt der Standard-Status.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Tables\Actions\DeleteAction $action, SolarPlantStatus $record) {
                            if (!$record->canBeDeleted()) {
                                Notification::make()
                                    ->title('Status kann nicht gelöscht werden')
                                    ->body('Standard-Status oder Status mit zugeordneten Solaranlagen können nicht gelöscht werden.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $cannotDelete = $records->filter(fn ($record) => !$record->canBeDeleted());
                            if ($cannotDelete->count() > 0) {
                                Notification::make()
                                    ->title('Einige Status können nicht gelöscht werden')
                                    ->body('Standard-Status oder Status mit zugeordneten Solaranlagen können nicht gelöscht werden.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->recordUrl(null)
            ->striped()
            ->emptyStateHeading('Keine Status vorhanden')
            ->emptyStateDescription('Erstellen Sie den ersten Status für Solaranlagen.')
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolarPlantStatuses::route('/'),
            'create' => Pages\CreateSolarPlantStatus::route('/create'),
            'edit' => Pages\EditSolarPlantStatus::route('/{record}/edit'),
        ];
    }
}
