<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppTokenResource\Pages;
use App\Filament\Resources\AppTokenResource\RelationManagers;
use App\Models\AppToken;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Services\AppTokenQrCodeService;

class AppTokenResource extends Resource
{
    protected static ?string $model = AppToken::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Benutzer';

    protected static ?string $modelLabel = 'App-Token';

    protected static ?string $pluralModelLabel = 'App-Tokens';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Token-Informationen')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Benutzer')
                            ->options(User::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id()),

                        Forms\Components\TextInput::make('name')
                            ->label('Token-Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. "iPhone App", "Desktop Client"')
                            ->hint('Eindeutiger Name für diesen Token'),

                        Forms\Components\Select::make('app_type')
                            ->label('App-Typ')
                            ->options(AppToken::getAppTypes())
                            ->required()
                            ->default('mobile_app'),

                        Forms\Components\TextInput::make('app_version')
                            ->label('App-Version')
                            ->maxLength(20)
                            ->placeholder('z.B. "1.0.0"'),
                    ])->columns(2),

                Section::make('Berechtigungen')
                    ->schema([
                        Forms\Components\CheckboxList::make('abilities')
                            ->label('Token-Berechtigungen')
                            ->options(AppToken::getAvailableAbilities())
                            ->required()
                            ->default(['tasks:read'])
                            ->columns(3)
                            ->hint('Wählen Sie die Berechtigungen aus, die dieser Token haben soll'),
                    ]),

                Section::make('Zusätzliche Informationen')
                    ->schema([
                        Forms\Components\Textarea::make('device_info')
                            ->label('Geräteinformationen')
                            ->rows(3)
                            ->placeholder('z.B. "iPhone 12 Pro, iOS 15.0"'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->placeholder('Zusätzliche Informationen zu diesem Token'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Token aktiv')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Token-Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('user.name')
                    ->label('Benutzer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('app_type_label')
                    ->label('App-Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mobile App' => 'info',
                        'Desktop App' => 'success',
                        'Web App' => 'primary',
                        'Third Party' => 'warning',
                        'Integration' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('abilities')
                    ->label('Berechtigungen')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->formatStateUsing(function ($state) {
                        $abilities = AppToken::getAvailableAbilities();
                        return $abilities[$state] ?? $state;
                    }),

                BadgeColumn::make('status_label')
                    ->label('Status')
                    ->color(fn (AppToken $record): string => $record->status_color),

                TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn (AppToken $record): string => $record->expires_at < now()->addDays(30) ? 'danger' : 'success'),

                TextColumn::make('last_used_at')
                    ->label('Zuletzt verwendet')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nie verwendet'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Benutzer')
                    ->options(User::active()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('app_type')
                    ->label('App-Typ')
                    ->options(AppToken::getAppTypes()),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Deaktiviert',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Läuft bald ab')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon()),

                Tables\Filters\Filter::make('expired')
                    ->label('Abgelaufen')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Action::make('show_qr_code')
                    ->label('QR-Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalHeading('Token QR-Code')
                    ->modalSubheading(fn (AppToken $record) => "QR-Code für Token: {$record->name}")
                    ->modalContent(function (AppToken $record) {
                        $qrCodeService = new AppTokenQrCodeService();
                        
                        // Hinweis: Wir können den echten Token nicht mehr anzeigen, da er gehasht ist
                        // Stattdessen zeigen wir eine Meldung
                        $warningMessage = "
                            <div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4'>
                                <div class='flex items-center'>
                                    <svg class='w-5 h-5 text-yellow-400 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                                        <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                                    </svg>
                                    <span class='text-yellow-800 font-medium'>Sicherheitshinweis</span>
                                </div>
                                <p class='text-yellow-700 mt-2'>
                                    Der QR-Code kann nur bei der Token-Erstellung angezeigt werden, da der Token aus Sicherheitsgründen verschlüsselt gespeichert wird.
                                </p>
                            </div>
                        ";
                        
                        $infoMessage = "
                            <div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>
                                <div class='flex items-center'>
                                    <svg class='w-5 h-5 text-blue-400 mr-2' fill='currentColor' viewBox='0 0 20 20'>
                                        <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'></path>
                                    </svg>
                                    <span class='text-blue-800 font-medium'>Token-Informationen</span>
                                </div>
                                <div class='text-blue-700 mt-2 space-y-1'>
                                    <p><strong>Name:</strong> {$record->name}</p>
                                    <p><strong>App-Typ:</strong> {$record->app_type_label}</p>
                                    <p><strong>Berechtigungen:</strong> " . implode(', ', $record->abilities_labels) . "</p>
                                    <p><strong>Erstellt:</strong> " . $record->created_at->format('d.m.Y H:i') . "</p>
                                    <p><strong>Läuft ab:</strong> " . $record->expires_at->format('d.m.Y H:i') . "</p>
                                </div>
                            </div>
                        ";
                        
                        return new \Illuminate\Support\HtmlString($warningMessage . $infoMessage);
                    })
                    ->modalWidth('lg'),

                Action::make('renew')
                    ->label('Erneuern')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (AppToken $record) {
                        $record->renew();
                        Notification::make()
                            ->title('Token erneuert')
                            ->body("Token '{$record->name}' wurde um 2 Jahre verlängert.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Token erneuern')
                    ->modalSubheading('Möchten Sie die Gültigkeit dieses Tokens um 2 Jahre verlängern?'),

                Action::make('toggle_active')
                    ->label(fn (AppToken $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (AppToken $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (AppToken $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (AppToken $record) {
                        if ($record->is_active) {
                            $record->disable();
                            $message = "Token '{$record->name}' wurde deaktiviert.";
                        } else {
                            $record->enable();
                            $message = "Token '{$record->name}' wurde aktiviert.";
                        }
                        
                        Notification::make()
                            ->title('Token-Status geändert')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->disable();
                            Notification::make()
                                ->title('Tokens deaktiviert')
                                ->body(count($records) . ' Token(s) wurden deaktiviert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('enable')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->enable();
                            Notification::make()
                                ->title('Tokens aktiviert')
                                ->body(count($records) . ' Token(s) wurden aktiviert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAppTokens::route('/'),
            'create' => Pages\CreateAppToken::route('/create'),
            'edit' => Pages\EditAppToken::route('/{record}/edit'),
        ];
    }
}
