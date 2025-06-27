<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StandardNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen - Standard';

    protected static ?string $modelLabel = 'Standard-Notiz';

    protected static ?string $pluralModelLabel = 'Standard-Notizen';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Null-Prüfung hinzufügen, um den Fehler zu vermeiden
        if ($query === null) {
            // Fallback: Direkte Query auf das Model erstellen
            $query = $this->getRelationship()->getQuery();
        }
        
        return $query->where('is_favorite', false)
                    ->orderBy('created_at', 'desc');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Standard-Notiz')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('Inhalt')
                            ->required()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Als Favorit markieren')
                            ->default(false),
                        Forms\Components\TextInput::make('created_by')
                            ->label('Erstellt von')
                            ->default(auth()->user()?->name ?? 'System')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon('heroicon-o-heart')
                    ->color('gray')
                    ->action(function ($record) {
                        // Neue Favoriten-Notiz: sort_order setzen
                        $maxSortOrder = $record->supplier->notes()
                            ->where('is_favorite', true)
                            ->max('sort_order') ?? 0;
                        
                        $record->update([
                            'is_favorite' => true,
                            'sort_order' => $maxSortOrder + 1
                        ]);
                        
                        // Notification anzeigen
                        \Filament\Notifications\Notification::make()
                            ->title('Zu Favoriten hinzugefügt')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Zu Favoriten hinzufügen'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('short_content')
                    ->label('Inhalt')
                    ->searchable(['content'])
                    ->limit(80)
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        // HTML-Content für Links in neuen Tabs vorbereiten
                        $content = $record->short_content;
                        return preg_replace('/<a\s+([^>]*?)href=(["\'])([^"\']*?)\2([^>]*?)>/i', '<a $1href=$2$3$2$4 target="_blank" rel="noopener noreferrer">', $content);
                    })
                    ->tooltip(fn ($record) => strip_tags($record->content)),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Erstellt von')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_today')
                    ->label('Heute erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('created_this_week')
                    ->label('Diese Woche erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Standard-Notiz hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->user()?->name ?? 'System';
                        
                        // Wenn als Favorit markiert, sort_order setzen
                        if ($data['is_favorite'] ?? false) {
                            $maxSortOrder = \App\Models\SupplierNote::where('supplier_id', $this->getOwnerRecord()->id)
                                ->where('is_favorite', true)
                                ->max('sort_order') ?? 0;
                            $data['sort_order'] = $maxSortOrder + 1;
                        } else {
                            $data['sort_order'] = 0;
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Standard-Notiz Details')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titel')
                                    ->disabled(),
                                Forms\Components\View::make('filament.forms.components.rich-content-display')
                                    ->viewData(fn ($record) => ['content' => $record->content])
                                    ->label('Inhalt'),
                                Forms\Components\TextInput::make('created_by')
                                    ->label('Erstellt von')
                                    ->disabled(),
                                Forms\Components\TextInput::make('formatted_created_at')
                                    ->label('Erstellt am')
                                    ->disabled(),
                            ]),
                    ]),
                Tables\Actions\EditAction::make()
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Wenn als Favorit markiert, sort_order setzen
                        if (($data['is_favorite'] ?? false) && !isset($data['sort_order'])) {
                            $maxSortOrder = \App\Models\SupplierNote::where('supplier_id', $this->getOwnerRecord()->id)
                                ->where('is_favorite', true)
                                ->max('sort_order') ?? 0;
                            $data['sort_order'] = $maxSortOrder + 1;
                        } elseif (!($data['is_favorite'] ?? false)) {
                            // Wenn nicht mehr Favorit, sort_order zurücksetzen
                            $data['sort_order'] = 0;
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Standard-Notizen vorhanden')
            ->emptyStateDescription('Erstellen Sie die erste Standard-Notiz für diesen Lieferanten.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}