<?php

namespace App\Filament\Resources\SolarPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class FavoriteNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->notes()->where('is_favorite', true)->count();
    }

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
        
        // Null-Prüfung hinzufügen, um den Fehler zu vermeiden
        if ($query === null) {
            // Fallback: Direkte Query auf das Model erstellen
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
                        Forms\Components\Select::make('type')
                            ->label('Typ')
                            ->options([
                                'general' => 'Allgemein',
                                'maintenance' => 'Wartung',
                                'improvement' => 'Verbesserung',
                                'issue' => 'Problem',
                                'planning' => 'Planung',
                                'installation' => 'Installation',
                                'commissioning' => 'Inbetriebnahme',
                                'monitoring' => 'Überwachung',
                                'performance' => 'Leistung',
                                'financial' => 'Finanziell',
                            ])
                            ->default('general')
                            ->required(),
                        Forms\Components\Hidden::make('is_favorite')
                            ->default(true),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
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
                        
                        // Notification anzeigen
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'general' => 'Allgemein',
                        'maintenance' => 'Wartung',
                        'improvement' => 'Verbesserung',
                        'issue' => 'Problem',
                        'planning' => 'Planung',
                        'installation' => 'Installation',
                        'commissioning' => 'Inbetriebnahme',
                        'monitoring' => 'Überwachung',
                        'performance' => 'Leistung',
                        'financial' => 'Finanziell',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'general' => 'gray',
                        'maintenance' => 'warning',
                        'improvement' => 'success',
                        'issue' => 'danger',
                        'planning' => 'info',
                        'installation' => 'primary',
                        'commissioning' => 'success',
                        'monitoring' => 'info',
                        'performance' => 'success',
                        'financial' => 'warning',
                        default => 'gray',
                    }),
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
                Tables\Columns\TextColumn::make('user.name')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'maintenance' => 'Wartung',
                        'improvement' => 'Verbesserung',
                        'issue' => 'Problem',
                        'planning' => 'Planung',
                        'installation' => 'Installation',
                        'commissioning' => 'Inbetriebnahme',
                        'monitoring' => 'Überwachung',
                        'performance' => 'Leistung',
                        'financial' => 'Finanziell',
                    ]),
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
                        $data['user_id'] = auth()->id();
                        $data['is_favorite'] = true;
                        
                        // Sort order setzen
                        $maxSortOrder = \App\Models\SolarPlantNote::where('solar_plant_id', $this->getOwnerRecord()->id)
                            ->where('is_favorite', true)
                            ->max('sort_order') ?? 0;
                        $data['sort_order'] = $maxSortOrder + 1;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalWidth('4xl')
                        ->form([
                            Forms\Components\Section::make('Favoriten-Notiz Details')
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Titel')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('type')
                                        ->label('Typ')
                                        ->formatStateUsing(fn ($state) => match($state) {
                                            'general' => 'Allgemein',
                                            'maintenance' => 'Wartung',
                                            'improvement' => 'Verbesserung',
                                            'issue' => 'Problem',
                                            'planning' => 'Planung',
                                            'installation' => 'Installation',
                                            'commissioning' => 'Inbetriebnahme',
                                            'monitoring' => 'Überwachung',
                                            'performance' => 'Leistung',
                                            'financial' => 'Finanziell',
                                            default => $state,
                                        })
                                        ->disabled(),
                                    Forms\Components\View::make('filament.forms.components.rich-content-display')
                                        ->viewData(fn ($record) => ['content' => $record->content])
                                        ->label('Inhalt'),
                                    Forms\Components\TextInput::make('user.name')
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
                ->icon('heroicon-o-cog-6-tooth')
                ->tooltip('Aktionen')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Keine Favoriten-Notizen vorhanden')
            ->emptyStateDescription('Erstellen Sie die erste Favoriten-Notiz für diese Solaranlage.')
            ->emptyStateIcon('heroicon-o-heart');
    }
}