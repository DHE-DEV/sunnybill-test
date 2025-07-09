<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms;
use App\Models\User;
use App\Models\Team;

class NotificationsPage extends Page implements HasTable, HasActions
{
    use InteractsWithTable;
    use InteractsWithActions;

    public string $currentSort = 'date';
    public string $activeTab = 'received';
    public bool $showStatistics = false;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    
    protected static ?string $slug = 'notifications';
    
    protected static ?string $title = 'Benachrichtigungen';
    
    protected static ?string $navigationLabel = 'Benachrichtigungen';
    
    protected static ?int $navigationSort = 11;
    
    protected static string $view = 'filament.pages.notifications-page';


    public function getTitle(): string
    {
        return 'Benachrichtigungen';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleStatistics')
                ->label($this->showStatistics ? 'Statistiken ausblenden' : 'Statistiken anzeigen')
                ->icon($this->showStatistics ? 'heroicon-o-chart-bar-square' : 'heroicon-o-chart-bar')
                ->color($this->showStatistics ? 'warning' : 'info')
                ->action(function () {
                    $this->showStatistics = !$this->showStatistics;
                }),
            
            Action::make('markAllAsRead')
                ->label('Alle als gelesen markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    Auth::user()->markAllNotificationsAsRead();
                    $this->dispatch('refresh-notifications');
                })
                ->visible(fn () => Auth::user()->unread_notifications_count > 0),
            
            Action::make('createNotification')
                ->label('Neue Benachrichtigung')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('recipient_type')
                        ->label('Empfänger-Typ')
                        ->options([
                            'user' => 'Einzelner Benutzer',
                            'team' => 'Team',
                        ])
                        ->default('user')
                        ->required()
                        ->reactive(),
                    
                    Forms\Components\Select::make('user_id')
                        ->label('Benutzer')
                        ->options(User::active()->pluck('name', 'id'))
                        ->searchable()
                        ->required(fn (callable $get) => $get('recipient_type') === 'user')
                        ->visible(fn (callable $get) => $get('recipient_type') === 'user')
                        ->placeholder('Benutzer auswählen'),
                    
                    Forms\Components\Select::make('team_id')
                        ->label('Team')
                        ->options(Team::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required(fn (callable $get) => $get('recipient_type') === 'team')
                        ->visible(fn (callable $get) => $get('recipient_type') === 'team')
                        ->placeholder('Team auswählen'),
                    
                    Forms\Components\TextInput::make('title')
                        ->label('Titel')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Titel der Benachrichtigung'),
                    
                    Forms\Components\Textarea::make('message')
                        ->label('Nachricht')
                        ->required()
                        ->rows(3)
                        ->placeholder('Nachrichtentext'),
                    
                    Forms\Components\Select::make('type')
                        ->label('Typ')
                        ->options([
                            'system' => 'System',
                            'task' => 'Aufgabe',
                            'billing' => 'Rechnung',
                            'customer' => 'Kunde',
                            'solar_plant' => 'Solaranlage',
                        ])
                        ->default('system')
                        ->required(),
                    
                    Forms\Components\Select::make('priority')
                        ->label('Priorität')
                        ->options([
                            'low' => 'Niedrig',
                            'normal' => 'Normal',
                            'high' => 'Hoch',
                            'urgent' => 'Dringend',
                        ])
                        ->default('normal')
                        ->required(),
                    
                    Forms\Components\Select::make('color')
                        ->label('Farbe')
                        ->options([
                            'primary' => 'Blau (Primary)',
                            'success' => 'Grün (Success)',
                            'warning' => 'Orange (Warning)',
                            'danger' => 'Rot (Danger)',
                            'info' => 'Hellblau (Info)',
                        ])
                        ->default('primary')
                        ->required(),
                    
                    Forms\Components\TextInput::make('action_url')
                        ->label('Aktions-URL (optional)')
                        ->url()
                        ->placeholder('https://example.com'),
                    
                    Forms\Components\TextInput::make('action_text')
                        ->label('Aktions-Text (optional)')
                        ->placeholder('Link öffnen'),
                    
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Ablaufdatum (optional)')
                        ->placeholder('Wann soll die Benachrichtigung ablaufen?')
                        ->seconds(false),
                ])
                ->action(function (array $data) {
                    $notificationData = [
                        'created_by' => Auth::id(),
                        'type' => $data['type'],
                        'title' => $data['title'],
                        'message' => $data['message'],
                        'priority' => $data['priority'],
                        'color' => $data['color'],
                        'action_url' => $data['action_url'] ?? null,
                        'action_text' => $data['action_text'] ?? null,
                        'expires_at' => $data['expires_at'] ?? null,
                        'is_read' => false,
                        'recipient_type' => $data['recipient_type'],
                    ];
                    
                    if ($data['recipient_type'] === 'user') {
                        $notificationData['user_id'] = $data['user_id'];
                        $recipient = User::find($data['user_id']);
                        $recipientName = $recipient->name;
                    } else {
                        $notificationData['team_id'] = $data['team_id'];
                        $team = Team::find($data['team_id']);
                        $recipientName = "Team {$team->name}";
                    }
                    
                    $notification = Notification::create($notificationData);
                    
                    // Push-Benachrichtigung an Empfänger senden
                    if ($data['recipient_type'] === 'user') {
                        $recipient = User::find($data['user_id']);
                        if ($recipient) {
                            \Filament\Notifications\Notification::make()
                                ->title($data['title'])
                                ->body($data['message'])
                                ->icon('heroicon-o-bell')
                                ->color($data['color'])
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Anzeigen')
                                        ->url('/admin/notifications')
                                        ->button(),
                                ])
                                ->sendToDatabase($recipient);
                        }
                    } else {
                        // Team-Benachrichtigung: An alle Team-Mitglieder senden
                        $team = Team::find($data['team_id']);
                        if ($team && $team->users) {
                            foreach ($team->users as $teamUser) {
                                \Filament\Notifications\Notification::make()
                                    ->title($data['title'])
                                    ->body($data['message'])
                                    ->icon('heroicon-o-bell')
                                    ->color($data['color'])
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('Anzeigen')
                                            ->url('/admin/notifications')
                                            ->button(),
                                    ])
                                    ->sendToDatabase($teamUser);
                            }
                        }
                    }
                    
                    $this->resetTable();
                    
                    // Erfolgs-Benachrichtigung für den Ersteller
                    \Filament\Notifications\Notification::make()
                        ->title('Benachrichtigung erstellt')
                        ->body("Benachrichtigung für {$recipientName} wurde erfolgreich erstellt.")
                        ->success()
                        ->send();
                })
                ->modalHeading('Neue Benachrichtigung erstellen')
                ->modalWidth(MaxWidth::Large),
        ];
    }

    public function table(Table $table): Table
    {
        $query = Notification::query()
            ->with(['user', 'creator', 'team'])
            ->notExpired();

        // Filter basierend auf aktivem Tab
        if ($this->activeTab === 'received') {
            $query->where(function ($q) {
                $q->where('user_id', Auth::id())
                  ->orWhereHas('team.users', function ($teamQuery) {
                      $teamQuery->where('user_id', Auth::id());
                  });
            });
        } else {
            $query->where('created_by', Auth::id());
        }

        // Spezielle Behandlung für Prioritätssortierung
        if ($this->currentSort === 'priority') {
            $query->orderByRaw("
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
            ")->orderBy('created_at', 'desc');
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (Notification $record): string => $record->icon)
                    ->color(fn (Notification $record): string => $record->color)
                    ->size('lg')
                    ->width('60px'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->weight('bold')
                    ->color(fn (Notification $record): string => $record->is_read ? 'gray' : 'primary')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Notification $record): string => $record->message)
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gmail_email' => 'Gmail',
                        'system' => 'System',
                        'task' => 'Aufgabe',
                        'billing' => 'Rechnung',
                        'customer' => 'Kunde',
                        'solar_plant' => 'Solaranlage',
                        default => ucfirst($state),
                    })
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('120px'),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn (Notification $record): string => $record->getPriorityText())
                    ->color(fn (Notification $record): string => match ($record->priority) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('120px'),

                Tables\Columns\BadgeColumn::make('creator.name')
                    ->label('Absender')
                    ->formatStateUsing(function (Notification $record): string {
                        if (!$record->creator) {
                            return 'System';
                        }
                        
                        // Wenn der aktuelle Benutzer der Ersteller ist, zeige "Ich"
                        $currentUser = auth()->user();
                        if ($currentUser && $record->creator->id === $currentUser->id) {
                            return 'Ich';
                        }
                        
                        return $record->creator->name;
                    })
                    ->color(function (Notification $record): string {
                        if (!$record->creator) {
                            return 'gray';
                        }
                        
                        // Wenn der aktuelle Benutzer der Ersteller ist, zeige grau
                        $currentUser = auth()->user();
                        if ($currentUser && $record->creator->id === $currentUser->id) {
                            return 'gray';
                        }
                        
                        // Andere Absender hellblau
                        return 'info';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('150px'),

                Tables\Columns\TextColumn::make('recipient_display')
                    ->label('Empfänger')
                    ->getStateUsing(function (Notification $record): string {
                        $currentUser = auth()->user();
                        
                        // Debug: Zeige alle relevanten Daten
                        \Log::info('Recipient Debug', [
                            'recipient_type' => $record->recipient_type,
                            'user_id' => $record->user_id,
                            'team_id' => $record->team_id,
                            'current_user_id' => $currentUser?->id,
                            'user_loaded' => $record->user ? $record->user->name : 'null',
                            'team_loaded' => $record->team ? $record->team->name : 'null'
                        ]);
                        
                        // Für Team-Benachrichtigungen
                        if ($record->recipient_type === 'team') {
                            if ($record->team_id && $record->team) {
                                return $record->team->name;
                            }
                            return 'Team (ID: ' . $record->team_id . ')';
                        }
                        
                        // Für Benutzer-Benachrichtigungen
                        if ($record->recipient_type === 'user') {
                            if ($currentUser && $record->user_id === $currentUser->id) {
                                return 'Ich';
                            }
                            if ($record->user_id && $record->user) {
                                return $record->user->name;
                            }
                            return 'Benutzer (ID: ' . $record->user_id . ')';
                        }
                        
                        return 'Typ: ' . $record->recipient_type;
                    })
                    ->badge()
                    ->color(function (Notification $record): string {
                        if ($record->recipient_type === 'team') {
                            return 'primary';
                        }
                        return 'gray';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('recipient_type', $direction)
                                   ->orderBy('user_id', $direction)
                                   ->orderBy('team_id', $direction);
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('150px'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(fn () => $this->activeTab === 'sent' ? 'Gesendet' : 'Erhalten')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('140px'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Zu erledigen bis')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(function (Notification $record): string {
                        if (!$record->expires_at) return 'gray';
                        if ($record->expires_at->isPast()) return 'danger';
                        if ($record->expires_at->diffInDays() <= 1) return 'warning';
                        return 'success';
                    })
                    ->placeholder('Kein Ablaufdatum')
                    ->tooltip(function (Notification $record): ?string {
                        if (!$record->expires_at) return null;
                        if ($record->expires_at->isPast()) return 'Abgelaufen';
                        return 'Läuft ab in ' . $record->expires_at->diffForHumans();
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('140px'),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Notification $record): string => $record->is_read ? 'Gelesen' : 'Ungelesen')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('100px'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('showReceived')
                    ->label('Empfangen')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color($this->activeTab === 'received' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->activeTab = 'received';
                        $this->resetTable();
                    }),
                
                Tables\Actions\Action::make('showSent')
                    ->label('Gesendet')
                    ->icon('heroicon-o-paper-airplane')
                    ->color($this->activeTab === 'sent' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->activeTab = 'sent';
                        $this->resetTable();
                    }),
                
                Tables\Actions\Action::make('sortByDate')
                    ->label('Nach Datum sortieren')
                    ->icon('heroicon-o-calendar')
                    ->color($this->currentSort === 'date' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->currentSort = 'date';
                        $this->resetTable();
                    }),
                
                Tables\Actions\Action::make('sortByPriority')
                    ->label('Nach Priorität sortieren')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color($this->currentSort === 'priority' ? 'primary' : 'gray')
                    ->action(function () {
                        $this->currentSort = 'priority';
                        $this->resetTable();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'gmail_email' => 'Gmail',
                        'system' => 'System',
                        'task' => 'Aufgabe',
                        'billing' => 'Rechnung',
                        'customer' => 'Kunde',
                        'solar_plant' => 'Solaranlage',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'urgent' => 'Dringend',
                        'high' => 'Hoch',
                        'normal' => 'Normal',
                        'low' => 'Niedrig',
                    ]),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Gelesen')
                    ->falseLabel('Ungelesen'),

                Tables\Filters\Filter::make('sender_filter')
                    ->label('Absender')
                    ->visible(fn () => $this->activeTab === 'received')
                    ->form([
                        Forms\Components\Select::make('sender')
                            ->label('Absender auswählen')
                            ->options(function () {
                                $users = User::active()->pluck('name', 'id')->toArray();
                                return ['' => 'Alle', 'system' => 'System'] + $users;
                            }),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['sender'])) {
                            return $query; // Alle anzeigen
                        }
                        if ($data['sender'] === 'system') {
                            return $query->whereNull('created_by');
                        }
                        return $query->where('created_by', $data['sender']);
                    }),

                Tables\Filters\Filter::make('recipient_filter')
                    ->label('Empfänger')
                    ->form([
                        Forms\Components\Select::make('recipient_type')
                            ->label('Empfänger-Typ')
                            ->options([
                                '' => 'Alle',
                                'user' => 'Benutzer',
                                'team' => 'Teams',
                            ])
                            ->reactive(),
                        
                        Forms\Components\Select::make('user_id')
                            ->label('Benutzer')
                            ->options(function () {
                                $users = User::active()->pluck('name', 'id')->toArray();
                                return ['' => 'Alle Benutzer'] + $users;
                            })
                            ->visible(fn (callable $get) => $get('recipient_type') === 'user' || $get('recipient_type') === ''),
                        
                        Forms\Components\Select::make('team_id')
                            ->label('Team')
                            ->options(function () {
                                $teams = Team::where('is_active', true)->pluck('name', 'id')->toArray();
                                return ['' => 'Alle Teams'] + $teams;
                            })
                            ->visible(fn (callable $get) => $get('recipient_type') === 'team' || $get('recipient_type') === ''),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['recipient_type'])) {
                            $query->where('recipient_type', $data['recipient_type']);
                        }
                        
                        if (!empty($data['user_id'])) {
                            $query->where('user_id', $data['user_id']);
                        }
                        
                        if (!empty($data['team_id'])) {
                            $query->where('team_id', $data['team_id']);
                        }
                        
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('expires_at_filter')
                    ->label('Ablaufdatum')
                    ->options([
                        'expired' => 'Abgelaufen',
                        'today' => 'Heute',
                        'this_week' => 'Diese Woche',
                        'this_month' => 'Dieser Monat',
                        'this_year' => 'Dieses Jahr',
                        'no_expiry' => 'Kein Ablaufdatum',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $value = $data['value'];
                        $now = now();
                        
                        switch ($value) {
                            case 'expired':
                                return $query->where('expires_at', '<', $now)->whereNotNull('expires_at');
                            
                            case 'today':
                                return $query->whereDate('expires_at', $now->toDateString());
                            
                            case 'this_week':
                                return $query->whereBetween('expires_at', [
                                    $now->copy()->startOfWeek(),
                                    $now->copy()->endOfWeek()
                                ]);
                            
                            case 'this_month':
                                return $query->whereBetween('expires_at', [
                                    $now->copy()->startOfMonth(),
                                    $now->copy()->endOfMonth()
                                ]);
                            
                            case 'this_year':
                                return $query->whereBetween('expires_at', [
                                    $now->copy()->startOfYear(),
                                    $now->copy()->endOfYear()
                                ]);
                            
                            case 'no_expiry':
                                return $query->whereNull('expires_at');
                            
                            default:
                                return $query;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('markAsRead')
                        ->label('Als gelesen markieren')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Notification $record) {
                            $record->markAsRead();
                            $this->dispatch('refresh-notifications');
                        })
                        ->visible(fn (Notification $record): bool => !$record->is_read),

                    Tables\Actions\Action::make('markAsUnread')
                        ->label('Als ungelesen markieren')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function (Notification $record) {
                            $record->markAsUnread();
                            $this->dispatch('refresh-notifications');
                        })
                        ->visible(fn (Notification $record): bool => $record->is_read),

                    Tables\Actions\Action::make('openAction')
                        ->label('Öffnen')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('primary')
                        ->url(fn (Notification $record): ?string => $record->action_url)
                        ->openUrlInNewTab()
                        ->visible(fn (Notification $record): bool => !empty($record->action_url)),

                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn (Notification $record): string => $record->title)
                        ->modalContent(function (Notification $record) {
                            return view('filament.pages.notification-details', ['notification' => $record]);
                        })
                        ->modalWidth(MaxWidth::Large)
                        ->action(function (Notification $record) {
                            if (!$record->is_read) {
                                $record->markAsRead();
                                $this->dispatch('refresh-notifications');
                            }
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->label('Löschen'),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markAsRead')
                    ->label('Als gelesen markieren')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->markAsRead();
                        }
                        $this->dispatch('refresh-notifications');
                    }),

                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh alle 30 Sekunden
            ->emptyStateHeading('Keine Benachrichtigungen')
            ->emptyStateDescription('Sie haben derzeit keine Benachrichtigungen.')
            ->emptyStateIcon('heroicon-o-bell-slash');
    }

    public function getTableRecordKey($record): string
    {
        return $record->getKey();
    }

    public function getTableRecordUrl($record): ?string
    {
        return $record->action_url;
    }

    public function getTableRecordUrlTarget($record): ?string
    {
        return $record->action_url ? '_blank' : null;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $receivedCount = Notification::query()
                ->where(function ($q) {
                    $q->where('user_id', Auth::id())
                      ->orWhereHas('team.users', function ($teamQuery) {
                          $teamQuery->where('user_id', Auth::id());
                      });
                })
                ->notExpired()
                ->count();
            
            return $receivedCount > 0 ? (string) $receivedCount : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $unreadCount = Notification::query()
                ->where(function ($q) {
                    $q->where('user_id', Auth::id())
                      ->orWhereHas('team.users', function ($teamQuery) {
                          $teamQuery->where('user_id', Auth::id());
                      });
                })
                ->unread()
                ->notExpired()
                ->count();
            
            $totalCount = Notification::query()
                ->where(function ($q) {
                    $q->where('user_id', Auth::id())
                      ->orWhereHas('team.users', function ($teamQuery) {
                          $teamQuery->where('user_id', Auth::id());
                      });
                })
                ->notExpired()
                ->count();
            
            // Rote Farbe wenn ungelesene Benachrichtigungen vorhanden
            if ($unreadCount > 0) {
                return 'danger';
            }
            
            // Blaue Farbe wenn nur gelesene Benachrichtigungen vorhanden
            if ($totalCount > 0) {
                return 'primary';
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function getStatistics(): array
    {
        $userId = Auth::id();
        
        // Basis-Query für empfangene Benachrichtigungen
        $receivedQuery = Notification::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('team.users', function ($teamQuery) use ($userId) {
                      $teamQuery->where('user_id', $userId);
                  });
            })
            ->notExpired();
        
        // Basis-Query für gesendete Benachrichtigungen
        $sentQuery = Notification::query()
            ->where('created_by', $userId)
            ->notExpired();
        
        return [
            'received' => [
                'total' => (clone $receivedQuery)->count(),
                'unread' => (clone $receivedQuery)->unread()->count(),
                'read' => (clone $receivedQuery)->read()->count(),
                'urgent' => (clone $receivedQuery)->where('priority', 'urgent')->count(),
                'high' => (clone $receivedQuery)->where('priority', 'high')->count(),
                'today' => (clone $receivedQuery)->whereDate('created_at', today())->count(),
                'this_week' => (clone $receivedQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'team_notifications' => (clone $receivedQuery)->whereNotNull('team_id')->count(),
                'personal_notifications' => (clone $receivedQuery)->whereNotNull('user_id')->whereNull('team_id')->count(),
            ],
            'sent' => [
                'total' => (clone $sentQuery)->count(),
                'today' => (clone $sentQuery)->whereDate('created_at', today())->count(),
                'this_week' => (clone $sentQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'urgent' => (clone $sentQuery)->where('priority', 'urgent')->count(),
                'team_notifications' => (clone $sentQuery)->whereNotNull('team_id')->count(),
                'personal_notifications' => (clone $sentQuery)->whereNotNull('user_id')->whereNull('team_id')->count(),
                'read_rate' => $this->calculateReadRate($sentQuery),
            ],
            'overview' => [
                'read_rate' => $this->calculateReadRate($receivedQuery),
                'avg_response_time' => $this->calculateAverageResponseTime($receivedQuery),
                'most_active_day' => $this->getMostActiveDay(),
                'priority_distribution' => $this->getPriorityDistribution($receivedQuery),
            ]
        ];
    }

    private function calculateReadRate($query): float
    {
        $total = (clone $query)->count();
        if ($total === 0) return 0;
        
        $read = (clone $query)->read()->count();
        return round(($read / $total) * 100, 1);
    }

    private function calculateAverageResponseTime($query): string
    {
        $notifications = (clone $query)
            ->read()
            ->whereNotNull('read_at')
            ->select('created_at', 'read_at')
            ->get();
        
        if ($notifications->isEmpty()) return 'N/A';
        
        $totalMinutes = $notifications->sum(function ($notification) {
            return $notification->created_at->diffInMinutes($notification->read_at);
        });
        
        $avgMinutes = $totalMinutes / $notifications->count();
        
        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' Min';
        } elseif ($avgMinutes < 1440) {
            return round($avgMinutes / 60, 1) . ' Std';
        } else {
            return round($avgMinutes / 1440, 1) . ' Tage';
        }
    }

    private function getMostActiveDay(): string
    {
        $days = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
        
        $dayStats = Notification::query()
            ->where('user_id', Auth::id())
            ->notExpired()
            ->selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as count')
            ->groupBy('day_of_week')
            ->orderBy('count', 'desc')
            ->first();
        
        if (!$dayStats) return 'N/A';
        
        // MySQL DAYOFWEEK: 1=Sonntag, 2=Montag, etc.
        $dayIndex = ($dayStats->day_of_week + 5) % 7; // Konvertierung zu 0=Montag
        return $days[$dayIndex];
    }

    private function getPriorityDistribution($query): array
    {
        $total = (clone $query)->count();
        if ($total === 0) return [];
        
        $priorities = (clone $query)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
        
        $distribution = [];
        foreach (['urgent', 'high', 'normal', 'low'] as $priority) {
            $count = $priorities[$priority] ?? 0;
            $percentage = round(($count / $total) * 100, 1);
            $distribution[$priority] = [
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        
        return $distribution;
    }
}
