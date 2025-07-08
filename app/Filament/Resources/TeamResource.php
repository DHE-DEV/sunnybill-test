<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\MaxWidth;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Teams';

    protected static ?string $modelLabel = 'Team';

    protected static ?string $pluralModelLabel = 'Teams';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team-Informationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Team-Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Entwicklung, Marketing, Support'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Beschreibung des Teams und seiner Aufgaben'),

                        Forms\Components\Select::make('color')
                            ->label('Farbe')
                            ->options(Team::getColors())
                            ->default('blue')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Inaktive Teams werden nicht in der Navigation angezeigt'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Team-Mitglieder')
                    ->schema([
                        Forms\Components\Placeholder::make('members_info')
                            ->label('Team-Mitglieder')
                            ->content('Team-Mitglieder können nach dem Erstellen des Teams über den "Mitglieder"-Tab verwaltet werden.')
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('')
                    ->width('40px'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Team-Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Mitglieder')
                    ->counts('users')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle Teams')
                    ->trueLabel('Nur aktive Teams')
                    ->falseLabel('Nur inaktive Teams'),

                Tables\Filters\SelectFilter::make('color')
                    ->label('Farbe')
                    ->options(Team::getColors()),
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
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn (Team $team) => $team->update(['is_active' => true]));
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn (Team $team) => $team->update(['is_active' => false]));
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Keine Teams vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihr erstes Team, um Benutzer zu organisieren.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'view' => Pages\ViewTeam::route('/{record}'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}