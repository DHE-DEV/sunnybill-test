<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GmailEmailResource\Pages;
use App\Models\GmailEmail;
use App\Services\GmailService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class GmailEmailResource extends Resource
{
    protected static ?string $model = GmailEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationLabel = 'Gmail E-Mails';
    
    protected static ?string $modelLabel = 'Gmail E-Mail';
    
    protected static ?string $pluralModelLabel = 'Gmail E-Mails';

    protected static ?string $navigationGroup = 'E-Mail';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('E-Mail-Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('subject')
                                    ->label('Betreff')
                                    ->disabled()
                                    ->columnSpanFull(),
                                
                                Forms\Components\Placeholder::make('from_display')
                                    ->label('Von')
                                    ->content(fn ($record) => $record ? $record->from_string : 'Unbekannt'),
                                
                                Forms\Components\Placeholder::make('to_display')
                                    ->label('An')
                                    ->content(fn ($record) => $record ? $record->to_string : 'Unbekannt'),
                                
                                Forms\Components\Placeholder::make('date_display')
                                    ->label('Datum')
                                    ->content(fn ($record) => $record && $record->gmail_date ? $record->gmail_date->format('d.m.Y H:i') : 'Unbekannt'),
                                
                                Forms\Components\Placeholder::make('size_display')
                                    ->label('Größe')
                                    ->content(fn ($record) => $record ? $record->readable_size : 'Unbekannt'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Toggle::make('is_read')
                                    ->label('Gelesen')
                                    ->disabled(),
                                
                                Forms\Components\Toggle::make('is_starred')
                                    ->label('Favorit')
                                    ->disabled(),
                                
                                Forms\Components\Toggle::make('has_attachments')
                                    ->label('Hat Anhänge')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('attachment_count')
                                    ->label('Anzahl Anhänge')
                                    ->disabled(),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Labels')
                    ->schema([
                        Forms\Components\TagsInput::make('labels')
                            ->label('Gmail Labels')
                            ->disabled(),
                    ]),
                
                Forms\Components\Section::make('Vorschau')
                    ->schema([
                        Forms\Components\Textarea::make('snippet')
                            ->label('Snippet')
                            ->rows(3)
                            ->disabled(),
                    ]),
                
                Forms\Components\Section::make('E-Mail-Inhalt')
                    ->schema([
                        Forms\Components\Tabs::make('content')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Formatiert')
                                    ->schema([
                                        Forms\Components\ViewField::make('formatted_html')
                                            ->label('E-Mail-Inhalt (formatiert)')
                                            ->view('filament.components.gmail-html-content')
                                            ->viewData(fn ($record) => [
                                                'html_content' => $record ? $record->body_html : '',
                                                'text_content' => $record ? $record->body_text : '',
                                                'subject' => $record ? $record->subject : '',
                                            ]),
                                    ]),
                                
                                Forms\Components\Tabs\Tab::make('Text')
                                    ->schema([
                                        Forms\Components\Textarea::make('body_text')
                                            ->label('Text-Inhalt')
                                            ->rows(15)
                                            ->disabled(),
                                    ]),
                                
                                Forms\Components\Tabs\Tab::make('HTML-Code')
                                    ->schema([
                                        Forms\Components\Textarea::make('body_html')
                                            ->label('HTML-Quellcode')
                                            ->rows(15)
                                            ->disabled(),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
                
                Forms\Components\Section::make('Anhänge')
                    ->schema([
                        Forms\Components\Repeater::make('attachments')
                            ->label('Anhänge')
                            ->schema([
                                Forms\Components\TextInput::make('filename')
                                    ->label('Dateiname')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('mimeType')
                                    ->label('MIME-Type')
                                    ->disabled(),
                                
                                Forms\Components\TextInput::make('size')
                                    ->label('Größe')
                                    ->disabled(),
                            ])
                            ->disabled()
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => $record && $record->has_attachments)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->icon(fn (string $state): string => $state ? 'heroicon-o-envelope-open' : 'heroicon-s-envelope')
                    ->color(fn (string $state): string => $state ? 'gray' : 'primary')
                    ->tooltip(fn (string $state): string => $state ? 'Gelesen' : 'Ungelesen')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_starred')
                    ->label('')
                    ->icon(fn (string $state): string => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (string $state): string => $state ? 'warning' : 'gray')
                    ->tooltip(fn (string $state): string => $state ? 'Favorit' : 'Kein Favorit')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('from_string')
                    ->label('Von')
                    ->searchable(['from'])
                    ->limit(30)
                    ->tooltip(function (GmailEmail $record): ?string {
                        return $record->from_string;
                    }),
                
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (GmailEmail $record): ?string {
                        return $record->subject;
                    }),
                
                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('')
                    ->icon(fn (string $state): string => $state ? 'heroicon-o-paper-clip' : '')
                    ->color('gray')
                    ->tooltip(fn (string $state, GmailEmail $record): string => 
                        $state ? "{$record->attachment_count} Anhang(e)" : 'Keine Anhänge'
                    ),
                
                Tables\Columns\TextColumn::make('gmail_date')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('readable_size')
                    ->label('Größe')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\BadgeColumn::make('labels')
                    ->label('Labels')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_slice($state, 0, 3)) : '')
                    ->colors([
                        'primary' => fn ($state) => in_array('INBOX', $state ?? []),
                        'success' => fn ($state) => in_array('SENT', $state ?? []),
                        'warning' => fn ($state) => in_array('IMPORTANT', $state ?? []),
                        'danger' => fn ($state) => in_array('SPAM', $state ?? []),
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Gelesen')
                    ->placeholder('Alle')
                    ->trueLabel('Nur gelesene')
                    ->falseLabel('Nur ungelesene'),
                
                Tables\Filters\TernaryFilter::make('is_starred')
                    ->label('Favorit')
                    ->placeholder('Alle')
                    ->trueLabel('Nur Favoriten')
                    ->falseLabel('Ohne Favoriten'),
                
                Tables\Filters\TernaryFilter::make('has_attachments')
                    ->label('Anhänge')
                    ->placeholder('Alle')
                    ->trueLabel('Mit Anhängen')
                    ->falseLabel('Ohne Anhänge'),
                
                Tables\Filters\Filter::make('gmail_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von Datum'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis Datum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('gmail_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('gmail_date', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('gmail_folder')
                    ->label('E-Mail-Ordner')
                    ->options([
                        'INBOX' => 'Posteingang',
                        'SENT' => 'Gesendet',
                        'DRAFT' => 'Entwürfe',
                        'ALL_MAIL' => 'Alle E-Mails',
                        'TRASH' => 'Papierkorb',
                        'SPAM' => 'Spam',
                    ])
                    ->default('INBOX')
                    ->query(function (Builder $query, array $data): Builder {
                        // Wenn kein Wert gesetzt ist, verwende INBOX als Standard
                        $value = $data['value'] ?? 'INBOX';
                        
                        switch ($value) {
                            case 'INBOX':
                                return $query->whereJsonContains('labels', 'INBOX')
                                           ->whereJsonDoesntContain('labels', 'TRASH');
                            
                            case 'SENT':
                                return $query->whereJsonContains('labels', 'SENT')
                                           ->whereJsonDoesntContain('labels', 'TRASH');
                            
                            case 'DRAFT':
                                return $query->whereJsonContains('labels', 'DRAFT')
                                           ->whereJsonDoesntContain('labels', 'TRASH');
                            
                            case 'ALL_MAIL':
                                return $query->whereJsonDoesntContain('labels', 'TRASH');
                            
                            case 'TRASH':
                                return $query->whereJsonContains('labels', 'TRASH');
                            
                            case 'SPAM':
                                return $query->whereJsonContains('labels', 'SPAM');
                            
                            default:
                                return $query->whereJsonContains('labels', 'INBOX')
                                           ->whereJsonDoesntContain('labels', 'TRASH');
                        }
                    }),
                
                Tables\Filters\SelectFilter::make('labels')
                    ->label('Zusätzliche Labels')
                    ->options([
                        'IMPORTANT' => 'Wichtig',
                        'STARRED' => 'Favoriten',
                        'UNREAD' => 'Ungelesen',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        return $query->whereJsonContains('labels', $data['value']);
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\Action::make('mark_read')
                        ->label('Als gelesen markieren')
                        ->icon('heroicon-o-envelope-open')
                        ->color('success')
                        ->visible(fn (GmailEmail $record) => !$record->is_read)
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->markAsRead($record->gmail_id)) {
                                    $record->markAsRead();
                                    Notification::make()
                                        ->title('E-Mail als gelesen markiert')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Markieren als gelesen');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('mark_unread')
                        ->label('Als ungelesen markieren')
                        ->icon('heroicon-s-envelope')
                        ->color('warning')
                        ->visible(fn (GmailEmail $record) => $record->is_read)
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->markAsUnread($record->gmail_id)) {
                                    $record->markAsUnread();
                                    Notification::make()
                                        ->title('E-Mail als ungelesen markiert')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Markieren als ungelesen');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('star')
                        ->label('Favorit hinzufügen')
                        ->icon('heroicon-s-star')
                        ->color('warning')
                        ->visible(fn (GmailEmail $record) => !$record->is_starred)
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->addLabels($record->gmail_id, ['STARRED'])) {
                                    Notification::make()
                                        ->title('Favorit hinzugefügt')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Hinzufügen des Favoriten');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('unstar')
                        ->label('Favorit entfernen')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->visible(fn (GmailEmail $record) => $record->is_starred)
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->removeLabels($record->gmail_id, ['STARRED'])) {
                                    Notification::make()
                                        ->title('Favorit entfernt')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Entfernen des Favoriten');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('move_to_trash')
                        ->label('In Papierkorb verschieben')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (GmailEmail $record) => !$record->is_trash)
                        ->requiresConfirmation()
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->moveToTrash($record->gmail_id)) {
                                    Notification::make()
                                        ->title('E-Mail in Papierkorb verschoben')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Verschieben in den Papierkorb');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Tables\Actions\Action::make('restore_from_trash')
                        ->label('Aus Papierkorb wiederherstellen')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->visible(fn (GmailEmail $record) => $record->is_trash)
                        ->action(function (GmailEmail $record) {
                            try {
                                $gmailService = new GmailService();
                                if ($gmailService->restoreFromTrash($record->gmail_id)) {
                                    Notification::make()
                                        ->title('E-Mail wiederhergestellt')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Fehler beim Wiederherstellen');
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_as_read')
                    ->label('Als gelesen markieren')
                    ->icon('heroicon-o-envelope-open')
                    ->color('success')
                    ->action(function ($records) {
                        $gmailService = new GmailService();
                        $success = 0;
                        $errors = 0;
                        
                        foreach ($records as $record) {
                            try {
                                if (!$record->is_read && $gmailService->markAsRead($record->gmail_id)) {
                                    $record->markAsRead();
                                    $success++;
                                }
                            } catch (\Exception $e) {
                                $errors++;
                            }
                        }
                        
                        Notification::make()
                            ->title("$success E-Mails als gelesen markiert")
                            ->body($errors > 0 ? "$errors Fehler aufgetreten" : '')
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\BulkAction::make('mark_as_unread')
                    ->label('Als ungelesen markieren')
                    ->icon('heroicon-s-envelope')
                    ->color('warning')
                    ->action(function ($records) {
                        $gmailService = new GmailService();
                        $success = 0;
                        $errors = 0;
                        
                        foreach ($records as $record) {
                            try {
                                if ($record->is_read && $gmailService->markAsUnread($record->gmail_id)) {
                                    $record->markAsUnread();
                                    $success++;
                                }
                            } catch (\Exception $e) {
                                $errors++;
                            }
                        }
                        
                        Notification::make()
                            ->title("$success E-Mails als ungelesen markiert")
                            ->body($errors > 0 ? "$errors Fehler aufgetreten" : '')
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\BulkAction::make('move_to_trash')
                    ->label('In Papierkorb verschieben')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $gmailService = new GmailService();
                        $success = 0;
                        $errors = 0;
                        
                        foreach ($records as $record) {
                            try {
                                if (!$record->is_trash && $gmailService->moveToTrash($record->gmail_id)) {
                                    $success++;
                                }
                            } catch (\Exception $e) {
                                $errors++;
                            }
                        }
                        
                        Notification::make()
                            ->title("$success E-Mails in Papierkorb verschoben")
                            ->body($errors > 0 ? "$errors Fehler aufgetreten" : '')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('gmail_date', 'desc')
            ->poll('30s'); // Auto-refresh alle 30 Sekunden
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGmailEmails::route('/'),
            'view' => Pages\ViewGmailEmail::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::unread()->where('is_trash', false)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unreadCount = static::getModel()::unread()->where('is_trash', false)->count();
        
        if ($unreadCount > 10) {
            return 'danger';
        } elseif ($unreadCount > 0) {
            return 'warning';
        }
        
        return null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getModel()::unread()->where('is_trash', false)->count();
        return $count === 1 ? '1 ungelesene E-Mail' : "{$count} ungelesene E-Mails";
    }

    public static function canCreate(): bool
    {
        return false; // E-Mails werden nur über Sync erstellt
    }

    public static function canEdit($record): bool
    {
        return false; // E-Mails sind read-only
    }

    public static function canDelete($record): bool
    {
        return false; // Löschung nur über Gmail-Aktionen
    }
}
