<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\SupplierContractNote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FavoriteNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'contractNotes';

    protected static ?string $title = 'Notizen';

    protected static ?string $modelLabel = 'Favoriten-Notiz';

    protected static ?string $pluralModelLabel = 'Favoriten-Notizen';

    protected static ?string $icon = 'heroicon-o-heart';

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        if ($query === null) {
            $query = $this->getRelationship()->getQuery();
        }
        
        return $query->where('is_favorite', true)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'desc');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Favoriten-Notiz')
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
                        Forms\Components\Hidden::make('is_favorite')
                            ->default(true),
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
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon('heroicon-s-heart')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->update(['is_favorite' => false, 'sort_order' => 0]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Aus Favoriten entfernt')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Aus Favoriten entfernen'),
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
                    ->label('Favoriten-Notiz hinzufügen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->user()?->name ?? 'System';
                        $data['is_favorite'] = true;
                        
                        $maxSortOrder = SupplierContractNote::where('supplier_contract_id', $this->getOwnerRecord()->id)
                            ->where('is_favorite', true)
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Favoriten-Notiz Details')
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
                        $data['is_favorite'] = true;
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Keine Favoriten-Notizen vorhanden')
            ->emptyStateDescription('Erstellen Sie die erste Favoriten-Notiz für diesen Vertrag.')
            ->emptyStateIcon('heroicon-o-heart');
    }
}