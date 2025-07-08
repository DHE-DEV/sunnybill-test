<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    protected static ?string $title = 'Team-Mitgliedschaften';

    protected static ?string $modelLabel = 'Team';

    protected static ?string $pluralModelLabel = 'Teams';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('team_id')
                    ->label('Team')
                    ->options(Team::active()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->placeholder('Team auswählen'),

                Forms\Components\Select::make('role')
                    ->label('Team-Rolle')
                    ->options(Team::getTeamRoles())
                    ->default('member')
                    ->required()
                    ->native(false),

                Forms\Components\DateTimePicker::make('joined_at')
                    ->label('Beigetreten am')
                    ->default(now())
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\BadgeColumn::make('pivot.role')
                    ->label('Meine Rolle')
                    ->formatStateUsing(fn (string $state): string => Team::getTeamRoles()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'lead' => 'warning',
                        'member' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Mitglieder')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pivot.joined_at')
                    ->label('Beigetreten')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Team aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pivot.role')
                    ->label('Meine Rolle')
                    ->options(Team::getTeamRoles()),

                Tables\Filters\SelectFilter::make('color')
                    ->label('Team-Farbe')
                    ->options(Team::getColors()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Team-Status')
                    ->placeholder('Alle Teams')
                    ->trueLabel('Nur aktive Teams')
                    ->falseLabel('Nur inaktive Teams'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Team beitreten')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Team')
                            ->placeholder('Team auswählen')
                            ->options(
                                Team::active()
                                    ->whereNotIn('id', $this->getOwnerRecord()->teams->pluck('id'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('role')
                            ->label('Team-Rolle')
                            ->options(Team::getTeamRoles())
                            ->default('member')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('joined_at')
                            ->label('Beigetreten am')
                            ->default(now())
                            ->required(),
                    ])
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Team-Rolle')
                            ->options(Team::getTeamRoles())
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('joined_at')
                            ->label('Beigetreten am')
                            ->required(),
                    ]),

                Tables\Actions\DetachAction::make()
                    ->label('Team verlassen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte Teams verlassen'),

                    Tables\Actions\BulkAction::make('changeRole')
                        ->label('Rolle in Teams ändern')
                        ->icon('heroicon-o-user-circle')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('Neue Team-Rolle')
                                ->options(Team::getTeamRoles())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->users()->updateExistingPivot(
                                    $this->getOwnerRecord()->id,
                                    ['role' => $data['role']]
                                );
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Keine Team-Mitgliedschaften')
            ->emptyStateDescription('Dieser Benutzer ist noch keinem Team beigetreten.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}