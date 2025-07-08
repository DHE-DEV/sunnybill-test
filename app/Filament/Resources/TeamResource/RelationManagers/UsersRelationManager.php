<?php

namespace App\Filament\Resources\TeamResource\RelationManagers;

use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Team-Mitglieder';

    protected static ?string $modelLabel = 'Mitglied';

    protected static ?string $pluralModelLabel = 'Mitglieder';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Benutzer')
                    ->options(User::active()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->placeholder('Benutzer auswählen'),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('E-Mail-Adresse kopiert'),

                Tables\Columns\BadgeColumn::make('pivot.role')
                    ->label('Team-Rolle')
                    ->formatStateUsing(fn (string $state): string => Team::getTeamRoles()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'lead' => 'warning',
                        'member' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('pivot.joined_at')
                    ->label('Beigetreten')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('System-Rolle')
                    ->formatStateUsing(fn (string $state): string => User::getRoles()[$state] ?? $state)
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pivot.role')
                    ->label('Team-Rolle')
                    ->options(Team::getTeamRoles()),

                Tables\Filters\SelectFilter::make('role')
                    ->label('System-Rolle')
                    ->options(User::getRoles()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle Benutzer')
                    ->trueLabel('Nur aktive Benutzer')
                    ->falseLabel('Nur inaktive Benutzer'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Mitglied hinzufügen')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Benutzer')
                            ->placeholder('Benutzer auswählen')
                            ->options(
                                User::active()
                                    ->whereNotIn('id', $this->getOwnerRecord()->users->pluck('id'))
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
                    ->label('Entfernen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen'),

                    Tables\Actions\BulkAction::make('changeRole')
                        ->label('Rolle ändern')
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
                                $this->getOwnerRecord()->users()->updateExistingPivot(
                                    $record->id,
                                    ['role' => $data['role']]
                                );
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Keine Mitglieder')
            ->emptyStateDescription('Fügen Sie Benutzer zu diesem Team hinzu.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }
}