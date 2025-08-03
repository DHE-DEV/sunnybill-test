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
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;

class GmailEmailResource extends Resource
{

    protected static ?string $model = GmailEmail::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationLabel = 'Gmail E-Mails';
    
    protected static ?string $modelLabel = 'Gmail E-Mail';
    
    protected static ?string $pluralModelLabel = 'Gmail E-Mails';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public string $currentSort = 'date';
    public string $activeTab = 'inbox';
    public bool $showStatistics = false;

    public static function getNavigationBadge(): ?string
    {
        try {
            $unreadCount = static::getModel()::unread()
                ->whereJsonContains('labels', 'INBOX')
                ->whereJsonDoesntContain('labels', 'TRASH')
                ->count();
                
            $readCount = static::getModel()::read()
                ->whereJsonContains('labels', 'INBOX')
                ->whereJsonDoesntContain('labels', 'TRASH')
                ->count();
            
            if ($unreadCount > 0 || $readCount > 0) {
                // Format: "Gelesen|Ungelesen" z.B. "1|2"
                return $readCount . '|' . $unreadCount;
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $unreadCount = static::getModel()::unread()
                ->whereJsonContains('labels', 'INBOX')
                ->whereJsonDoesntContain('labels', 'TRASH')
                ->count();
            
            // Rote Farbe wenn ungelesene E-Mails vorhanden, sonst blau
            return $unreadCount > 0 ? 'danger' : 'primary';
        } catch (\Exception $e) {
            return 'gray';
        }
    }

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
                                        Forms\Components\Textarea::make('body_html')
                                            ->label('E-Mail-Inhalt (formatiert)')
                                            ->rows(20)
                                            ->disabled()
                                            ->formatStateUsing(function ($state, $record) {
                                                if (!$record) return 'Kein Inhalt verfügbar';
                                                
                                                if (!empty($record->body_html)) {
                                                    return strip_tags($record->body_html);
                                                } elseif (!empty($record->body_text)) {
                                                    return $record->body_text;
                                                }
                                                
                                                return 'Kein E-Mail-Inhalt verfügbar';
                                            }),
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
                
                Forms\Components\Section::make('PDF-Anhänge')
                    ->schema([
                        Forms\Components\Repeater::make('pdf_attachments_display')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('filename')
                                            ->label('Dateiname')
                                            ->disabled(),
                                        
                                        Forms\Components\TextInput::make('size')
                                            ->label('Größe')
                                            ->disabled(),
                                        
                                        Forms\Components\TextInput::make('type')
                                            ->label('Typ')
                                            ->disabled(),
                                        
                                        Forms\Components\Hidden::make('attachment_id'),
                                    ]),
                                
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('download_url')
                                            ->label('Download-Link')
                                            ->disabled()
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('download')
                                                    ->icon('heroicon-o-arrow-down-tray')
                                                    ->url(fn ($state) => $state)
                                                    ->openUrlInNewTab(false)
                                            ),
                                        
                                        Forms\Components\TextInput::make('preview_url')
                                            ->label('Vorschau-Link')
                                            ->disabled()
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('preview')
                                                    ->icon('heroicon-o-eye')
                                                    ->url(fn ($state) => $state)
                                                    ->openUrlInNewTab(true)
                                            ),
                                    ]),
                                
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('analyze_pdf')
                                                ->label('PDF analysieren')
                                                ->icon('heroicon-o-document-magnifying-glass')
                                                ->color('info')
                                                ->action(function ($livewire, $state, $get) {
                                                    $record = $livewire->record;
                                                    $attachmentId = $get('attachment_id');
                                                    
                                                    if (!$attachmentId) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Fehler')
                                                            ->body('Anhang-ID nicht gefunden')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    try {
                                                        $analyzeUrl = route('gmail.attachment.analyze', [
                                                            'email' => $record->uuid,
                                                            'attachment' => $attachmentId
                                                        ]);
                                                        
                                                        // Redirect zur Analyse-URL in neuem Tab
                                                        $livewire->js("window.open('{$analyzeUrl}', '_blank');");
                                                        
                                                    } catch (\Exception $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Fehler')
                                                            ->body('Fehler beim Öffnen der PDF-Analyse: ' . $e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ])
                                    ]),
                            ])
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record || !$record->has_attachments || !$record->hasPdfAttachments()) {
                                    return [];
                                }
                                
                                $pdfAttachments = $record->getPdfAttachments();
                                if (empty($pdfAttachments)) {
                                    return [];
                                }
                                
                                $formattedAttachments = [];
                                foreach ($pdfAttachments as $attachment) {
                                    $filename = $attachment['filename'] ?? 'Unbekannte Datei';
                                    $size = isset($attachment['size']) ? number_format($attachment['size'] / 1024, 1) . ' KB' : 'Unbekannt';
                                    $attachmentId = $attachment['id'] ?? $attachment['attachmentId'] ?? null;
                                    
                                    $downloadUrl = '';
                                    $previewUrl = '';
                                    
                                    if ($attachmentId) {
                                        $downloadUrl = route('gmail.attachment.download', ['email' => $record->uuid, 'attachment' => $attachmentId]);
                                        $previewUrl = route('gmail.attachment.preview', ['email' => $record->uuid, 'attachment' => $attachmentId]);
                                    }
                                    
                                    $formattedAttachments[] = [
                                        'filename' => $filename,
                                        'size' => $size,
                                        'type' => 'PDF',
                                        'download_url' => $downloadUrl,
                                        'preview_url' => $previewUrl,
                                        'attachment_id' => $attachmentId,
                                    ];
                                }
                                
                                return $formattedAttachments;
                            }),
                    ])
                    ->visible(fn ($record) => $record && $record->has_attachments && $record->hasPdfAttachments()),
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
