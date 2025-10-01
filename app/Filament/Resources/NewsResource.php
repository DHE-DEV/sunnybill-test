<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource\RelationManagers;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Neuigkeiten';

    protected static ?string $modelLabel = 'Neuigkeit';

    protected static ?string $pluralModelLabel = 'Neuigkeiten';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grundinformationen')
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
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'heading',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Bild')
                            ->image()
                            ->directory('news-images')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120)
                            ->helperText('Optional: Fügen Sie ein Bild hinzu (max. 5MB)')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Veröffentlichung')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Nur aktive Neuigkeiten werden angezeigt')
                            ->inline(false),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priorität')
                            ->numeric()
                            ->default(0)
                            ->helperText('Höhere Werte werden zuerst angezeigt')
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Veröffentlichungsdatum')
                            ->default(now())
                            ->required()
                            ->helperText('Neuigkeiten werden nur Benutzern angezeigt, die nach diesem Datum erstellt wurden'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Bild')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorität')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Veröffentlicht am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('userViews_count')
                    ->label('Aufrufe')
                    ->counts('userViews')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Tables\Filters\Filter::make('published_at')
                    ->form([
                        Forms\Components\DatePicker::make('published_from')
                            ->label('Veröffentlicht von'),
                        Forms\Components\DatePicker::make('published_until')
                            ->label('Veröffentlicht bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['published_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date),
                            )
                            ->when(
                                $data['published_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
