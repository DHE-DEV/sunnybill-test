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

class NotificationsPage extends Page implements HasTable, HasActions
{
    use InteractsWithTable;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    
    protected static ?string $slug = 'notifications';
    
    protected static ?string $title = 'Benachrichtigungen';
    
    protected static ?string $navigationLabel = 'Benachrichtigungen';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.notifications-page';

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return '/admin/notifications';
    }

    public static function getNavigationUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return '/admin/notifications';
    }

    public function getTitle(): string
    {
        $unreadCount = Auth::user()->unread_notifications_count;
        return 'Benachrichtigungen' . ($unreadCount > 0 ? " ({$unreadCount})" : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Alle als gelesen markieren')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    Auth::user()->markAllNotificationsAsRead();
                    $this->dispatch('refresh-notifications');
                })
                ->visible(fn () => Auth::user()->unread_notifications_count > 0),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Notification::query()
                    ->where('user_id', Auth::id())
                    ->notExpired()
                    ->sorted()
            )
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (Notification $record): string => $record->icon)
                    ->color(fn (Notification $record): string => $record->color)
                    ->size('lg'),

                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('title')
                        ->weight('bold')
                        ->color(fn (Notification $record): string => $record->is_read ? 'gray' : 'primary')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('message')
                        ->color('gray')
                        ->limit(100)
                        ->searchable(),

                    Tables\Columns\Layout\Split::make([
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
                            ->color(fn (Notification $record): string => $record->color),

                        Tables\Columns\BadgeColumn::make('priority')
                            ->label('Priorität')
                            ->formatStateUsing(fn (Notification $record): string => $record->getPriorityText())
                            ->color(fn (Notification $record): string => match ($record->priority) {
                                'urgent' => 'danger',
                                'high' => 'warning',
                                'normal' => 'primary',
                                'low' => 'gray',
                                default => 'gray',
                            }),

                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Erhalten')
                            ->dateTime('d.m.Y H:i')
                            ->sortable()
                            ->color('gray'),
                    ]),
                ])->space(2),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Notification $record): string => $record->is_read ? 'Gelesen' : 'Ungelesen'),
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
            ])
            ->actions([
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

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
