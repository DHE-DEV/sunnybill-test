<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleVersionResource\Pages;
use App\Filament\Resources\ArticleVersionResource\RelationManagers;
use App\Models\ArticleVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleVersionResource extends Resource
{
    protected static ?string $model = ArticleVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Artikel Versionen';
    
    protected static ?string $modelLabel = 'Artikel Version';
    
    protected static ?string $pluralModelLabel = 'Artikel Versionen';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('article_id')
                    ->relationship('article', 'name')
                    ->required(),
                Forms\Components\TextInput::make('version_number')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('tax_rate')
                    ->required()
                    ->numeric()
                    ->default(0.1900),
                Forms\Components\TextInput::make('unit')
                    ->required()
                    ->maxLength(255)
                    ->default('Stück'),
                Forms\Components\TextInput::make('changed_by')
                    ->maxLength(255),
                Forms\Components\Textarea::make('change_reason')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('changed_fields'),
                Forms\Components\Toggle::make('is_current')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('article.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('version_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('changed_by')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListArticleVersions::route('/'),
            'create' => Pages\CreateArticleVersion::route('/create'),
            'edit' => Pages\EditArticleVersion::route('/{record}/edit'),
        ];
    }
}
