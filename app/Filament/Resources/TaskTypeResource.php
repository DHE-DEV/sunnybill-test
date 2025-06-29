<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskTypeResource\Pages;
use App\Filament\Resources\TaskTypeResource\RelationManagers;
use App\Models\TaskType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;

class TaskTypeResource extends Resource
{
    protected static ?string $model = TaskType::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Aufgaben';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Aufgabentyp';

    protected static ?string $pluralModelLabel = 'Aufgabentypen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Darstellung')
                    ->schema([
                        ColorPicker::make('color')
                            ->label('Farbe')
                            ->default('#3B82F6')
                            ->required(),

                        Select::make('icon')
                            ->label('Icon')
                            ->options([
                                'heroicon-o-clipboard-document-list' => 'Clipboard Document List',
                                'heroicon-o-document-text' => 'Document Text',
                                'heroicon-o-folder' => 'Folder',
                                'heroicon-o-calendar' => 'Calendar',
                                'heroicon-o-clock' => 'Clock',
                                'heroicon-o-check-circle' => 'Check Circle',
                                'heroicon-o-exclamation-triangle' => 'Exclamation Triangle',
                                'heroicon-o-star' => 'Star',
                                'heroicon-o-cog-6-tooth' => 'Settings',
                                'heroicon-o-wrench-screwdriver' => 'Tools',
                                'heroicon-o-phone' => 'Phone',
                                'heroicon-o-envelope' => 'Email',
                                'heroicon-o-user' => 'User',
                                'heroicon-o-users' => 'Users',
                                'heroicon-o-building-office' => 'Building',
                                'heroicon-o-truck' => 'Truck',
                                'heroicon-o-bolt' => 'Bolt',
                                'heroicon-o-sun' => 'Sun',
                            ])
                            ->default('heroicon-o-clipboard-document-list')
                            ->required()
                            ->searchable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Einstellungen')
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Sortierreihenfolge')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Reihenfolge')
                    ->sortable()
                    ->width(100),

                IconColumn::make('icon')
                    ->label('Icon')
                    ->icon(fn (string $state): string => $state)
                    ->color(fn (TaskType $record): string => $record->color)
                    ->width(60),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                ColorColumn::make('color')
                    ->label('Farbe')
                    ->width(80),

                ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),

                TextColumn::make('tasks_count')
                    ->label('Aufgaben')
                    ->counts('tasks')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Filter::make('has_tasks')
                    ->label('Mit Aufgaben')
                    ->query(fn (Builder $query): Builder => $query->has('tasks'))
                    ->toggle(),

                Filter::make('no_tasks')
                    ->label('Ohne Aufgaben')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('tasks'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (TaskType $record) {
                        if (!$record->canBeDeleted()) {
                            throw new \Exception('Aufgabentyp kann nicht gelöscht werden, da noch Aufgaben zugeordnet sind.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if (!$record->canBeDeleted()) {
                                    throw new \Exception("Aufgabentyp '{$record->name}' kann nicht gelöscht werden, da noch Aufgaben zugeordnet sind.");
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaskTypes::route('/'),
            'create' => Pages\CreateTaskType::route('/create'),
            'view' => Pages\ViewTaskType::route('/{record}'),
            'edit' => Pages\EditTaskType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }
}
