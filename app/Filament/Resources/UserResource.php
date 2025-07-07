<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Benutzerverwaltung';

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Benutzerinformationen')
                    ->description('Grundlegende Informationen des Benutzers')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('department')
                            ->label('Abteilung')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sicherheit & Berechtigung')
                    ->description('Passwort, Rolle und Berechtigungen')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(false)
                            ->minLength(8)
                            ->default(fn (string $context): string => $context === 'create' ? User::generateRandomPassword() : '')
                            ->helperText('Mindestens 8 Zeichen. Bei neuen Benutzern wird automatisch ein sicheres Passwort generiert. Bei der Bearbeitung leer lassen, um das aktuelle Passwort beizubehalten.'),

                        Forms\Components\Select::make('role')
                            ->label('Rolle')
                            ->options(User::getRoles())
                            ->required()
                            ->default('user')
                            ->helperText('Bestimmt die Berechtigung des Benutzers im System'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Benutzer können sich nicht anmelden'),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('E-Mail verifiziert am')
                            ->displayFormat('d.m.Y H:i')
                            ->helperText('Zeitpunkt der E-Mail-Verifizierung'),

                        Forms\Components\Toggle::make('password_change_required')
                            ->label('Passwort-Wechsel erforderlich')
                            ->helperText('Benutzer muss bei der nächsten Anmeldung das Passwort ändern')
                            ->visible(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\DateTimePicker::make('password_changed_at')
                            ->label('Passwort geändert am')
                            ->displayFormat('d.m.Y H:i')
                            ->helperText('Zeitpunkt der letzten Passwort-Änderung')
                            ->visible(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\TextInput::make('temporary_password')
                            ->label('Temporäres Passwort')
                            ->disabled()
                            ->helperText('Wird automatisch gelöscht, wenn der Benutzer sein Passwort ändert')
                            ->visible(fn (string $context, $record): bool => $context === 'edit' && $record && $record->hasTemporaryPassword()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zusätzliche Informationen')
                    ->description('Notizen und weitere Details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Interne Notizen zum Benutzer'),

                        Forms\Components\Placeholder::make('last_login_info')
                            ->label('Letzte Anmeldung')
                            ->content(function ($record) {
                                if (!$record || !$record->last_login_at) {
                                    return 'Noch nie angemeldet';
                                }
                                return $record->last_login_at->format('d.m.Y H:i') . ' Uhr';
                            })
                            ->visible(fn ($context) => $context === 'edit'),

                        Forms\Components\Placeholder::make('created_info')
                            ->label('Erstellt')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '-';
                                }
                                return $record->created_at->format('d.m.Y H:i') . ' Uhr';
                            })
                            ->visible(fn ($context) => $context === 'edit'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rolle')
                    ->formatStateUsing(fn (string $state): string => User::getRoles()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'user' => 'success',
                        'viewer' => 'gray',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('department')
                    ->label('Abteilung')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verifiziert')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('password_change_required')
                    ->label('Passwort-Wechsel')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Letzte Anmeldung')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nie')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rolle')
                    ->options(User::getRoles()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle Benutzer')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('E-Mail verifiziert')
                    ->placeholder('Alle')
                    ->trueLabel('Verifiziert')
                    ->falseLabel('Nicht verifiziert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                Tables\Filters\Filter::make('recent_login')
                    ->label('Kürzlich angemeldet')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(30)))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Benutzer deaktivieren' : 'Benutzer aktivieren')
                        ->modalDescription(fn ($record) => $record->is_active 
                            ? 'Sind Sie sicher, dass Sie diesen Benutzer deaktivieren möchten? Er kann sich dann nicht mehr anmelden.'
                            : 'Sind Sie sicher, dass Sie diesen Benutzer aktivieren möchten?'
                        )
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            
                            Notification::make()
                                ->title('Benutzer ' . ($record->is_active ? 'aktiviert' : 'deaktiviert'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reset_password')
                        ->label('Passwort zurücksetzen')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('Neues Passwort')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->confirmed(),
                            Forms\Components\TextInput::make('new_password_confirmation')
                                ->label('Passwort bestätigen')
                                ->password()
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'password' => Hash::make($data['new_password']),
                                'password_change_required' => true,
                                'password_changed_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Passwort erfolgreich zurückgesetzt')
                                ->body('Der Benutzer muss das Passwort bei der nächsten Anmeldung ändern.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('generate_random_password')
                        ->label('Zufälliges Passwort generieren')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Zufälliges Passwort generieren')
                        ->modalDescription(fn ($record) => "Möchten Sie ein neues zufälliges Passwort für {$record->name} generieren und per E-Mail versenden?")
                        ->action(function ($record) {
                            try {
                                $temporaryPassword = \App\Models\User::generateRandomPassword();
                                
                                $record->update([
                                    'password' => Hash::make($temporaryPassword),
                                    'temporary_password' => $temporaryPassword,
                                    'password_change_required' => true,
                                    'password_changed_at' => now(),
                                ]);
                                
                                // Sende E-Mail mit neuem Passwort
                                $record->notify(new \App\Notifications\NewUserPasswordNotification($temporaryPassword));
                                
                                Notification::make()
                                    ->title('Zufälliges Passwort generiert')
                                    ->body("Ein neues zufälliges Passwort wurde generiert und an {$record->email} gesendet.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim Generieren des Passworts')
                                    ->body("Das Passwort konnte nicht generiert werden: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('resend_temporary_password')
                        ->label('Temporäres Passwort erneut senden')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->visible(fn ($record) => $record->hasTemporaryPassword())
                        ->requiresConfirmation()
                        ->modalHeading('Temporäres Passwort erneut senden')
                        ->modalDescription(fn ($record) => "Möchten Sie das temporäre Passwort erneut an {$record->email} senden?")
                        ->action(function ($record) {
                            try {
                                if ($record->hasTemporaryPassword()) {
                                    // Sende E-Mail-Verifikation mit temporärem Passwort
                                    if (!$record->hasVerifiedEmail()) {
                                        $record->sendEmailVerificationNotification($record->getTemporaryPasswordForEmail());
                                        
                                        Notification::make()
                                            ->title('E-Mail-Verifikation mit temporärem Passwort gesendet')
                                            ->body("Eine E-Mail-Verifikation mit dem temporären Passwort wurde an {$record->email} gesendet.")
                                            ->success()
                                            ->send();
                                    } else {
                                        // Sende separate Passwort-E-Mail
                                        $record->notify(new \App\Notifications\NewUserPasswordNotification($record->getTemporaryPasswordForEmail()));
                                        
                                        Notification::make()
                                            ->title('Temporäres Passwort erneut gesendet')
                                            ->body("Das temporäre Passwort wurde erneut an {$record->email} gesendet.")
                                            ->success()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim Senden des temporären Passworts')
                                    ->body("Das temporäre Passwort konnte nicht gesendet werden: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('clear_temporary_password')
                        ->label('Temporäres Passwort löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn ($record) => $record->hasTemporaryPassword())
                        ->requiresConfirmation()
                        ->modalHeading('Temporäres Passwort löschen')
                        ->modalDescription(fn ($record) => "Möchten Sie das temporäre Passwort für {$record->name} löschen?")
                        ->action(function ($record) {
                            $record->clearTemporaryPassword();
                            
                            Notification::make()
                                ->title('Temporäres Passwort gelöscht')
                                ->body("Das temporäre Passwort wurde erfolgreich gelöscht.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('resend_verification')
                        ->label('E-Mail-Verifikation senden')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->visible(fn ($record) => !$record->hasVerifiedEmail())
                        ->requiresConfirmation()
                        ->modalHeading('E-Mail-Verifikation erneut senden')
                        ->modalDescription(fn ($record) => "Möchten Sie eine neue E-Mail-Verifikation an {$record->email} senden?")
                        ->action(function ($record) {
                            try {
                                $record->sendEmailVerificationNotification();
                                
                                Notification::make()
                                    ->title('E-Mail-Verifikation gesendet')
                                    ->body("Eine E-Mail-Bestätigung wurde an {$record->email} gesendet.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim Senden der E-Mail-Verifikation')
                                    ->body("Die E-Mail konnte nicht gesendet werden: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('mark_verified')
                        ->label('Als verifiziert markieren')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($record) => !$record->hasVerifiedEmail())
                        ->requiresConfirmation()
                        ->modalHeading('E-Mail als verifiziert markieren')
                        ->modalDescription(fn ($record) => "Möchten Sie die E-Mail-Adresse {$record->email} manuell als verifiziert markieren?")
                        ->action(function ($record) {
                            $record->markEmailAsVerified();
                            
                            // Sende Account-Aktivierungs-E-Mail
                            try {
                                $record->notify(new \App\Notifications\AccountActivatedNotification());
                                
                                Notification::make()
                                    ->title('E-Mail als verifiziert markiert')
                                    ->body("Die E-Mail-Adresse {$record->email} wurde als verifiziert markiert und eine Account-Aktivierungs-E-Mail wurde gesendet.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('E-Mail als verifiziert markiert')
                                    ->body("Die E-Mail-Adresse wurde als verifiziert markiert, aber die Account-Aktivierungs-E-Mail konnte nicht gesendet werden: " . $e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('send_activation')
                        ->label('Account-Aktivierungs-E-Mail senden')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->visible(fn ($record) => $record->hasVerifiedEmail())
                        ->requiresConfirmation()
                        ->modalHeading('Account-Aktivierungs-E-Mail senden')
                        ->modalDescription(fn ($record) => "Möchten Sie eine Account-Aktivierungs-E-Mail an {$record->email} senden?")
                        ->action(function ($record) {
                            try {
                                $record->notify(new \App\Notifications\AccountActivatedNotification());
                                
                                Notification::make()
                                    ->title('Account-Aktivierungs-E-Mail gesendet')
                                    ->body("Eine Account-Aktivierungs-E-Mail wurde an {$record->email} gesendet.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler beim Senden der Account-Aktivierungs-E-Mail')
                                    ->body("Die E-Mail konnte nicht gesendet werden: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Benutzer löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie diesen Benutzer permanent löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title(count($records) . ' Benutzer aktiviert')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title(count($records) . ' Benutzer deaktiviert')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Benutzer löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Benutzer permanent löschen möchten?'),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'department'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'E-Mail' => $record->email,
            'Rolle' => $record->role_label,
            'Abteilung' => $record->department,
        ];
    }
}
