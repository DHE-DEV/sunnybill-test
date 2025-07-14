<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Dokumententyp';

    protected static ?string $pluralModelLabel = 'Dokumententypen';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Grundinformationen')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Planung'),

                                Forms\Components\TextInput::make('key')
                                    ->label('Schlüssel')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('z.B. planning')
                                    ->helperText('Eindeutiger Schlüssel für interne Verwendung'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->placeholder('Beschreibung des Dokumententyps...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Darstellung')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('color')
                                    ->label('Farbe')
                                    ->options([
                                        'gray' => 'Grau',
                                        'primary' => 'Primär',
                                        'secondary' => 'Sekundär',
                                        'success' => 'Grün',
                                        'warning' => 'Gelb',
                                        'danger' => 'Rot',
                                        'info' => 'Blau',
                                    ])
                                    ->default('gray')
                                    ->required(),

                                Forms\Components\Select::make('icon')
                                    ->label('Icon')
                                    ->options([
                                        'heroicon-o-document' => 'Dokument',
                                        'heroicon-o-document-text' => 'Dokument Text',
                                        'heroicon-o-folder' => 'Ordner',
                                        'heroicon-o-photo' => 'Foto',
                                        'heroicon-o-clipboard-document' => 'Zwischenablage',
                                        'heroicon-o-archive-box' => 'Archiv',
                                        'heroicon-o-wrench-screwdriver' => 'Werkzeug',
                                        'heroicon-o-building-office' => 'Büro',
                                        'heroicon-o-banknotes' => 'Geld',
                                        'heroicon-o-academic-cap' => 'Zertifikat',
                                    ])
                                    ->default('heroicon-o-document')
                                    ->required(),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sortierreihenfolge')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                            ]),
                    ]),

                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Nur aktive Dokumententypen werden in der Auswahl angezeigt'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (DocumentType $record): string => $record->icon)
                    ->color(fn (DocumentType $record): string => $record->color)
                    ->size('lg'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('key')
                    ->label('Schlüssel')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->toggleable(),

                BadgeColumn::make('color')
                    ->label('Farbe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gray' => 'Grau',
                        'primary' => 'Primär',
                        'secondary' => 'Sekundär',
                        'success' => 'Grün',
                        'warning' => 'Gelb',
                        'danger' => 'Rot',
                        'info' => 'Blau',
                        default => $state,
                    })
                    ->color(fn (string $state): string => $state),

                TextColumn::make('sort_order')
                    ->label('Reihenfolge')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),

                TextColumn::make('documents_count')
                    ->label('Dokumente')
                    ->counts('documents')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Aktiv',
                        false => 'Inaktiv',
                    ]),

                SelectFilter::make('color')
                    ->label('Farbe')
                    ->options([
                        'gray' => 'Grau',
                        'primary' => 'Primär',
                        'secondary' => 'Sekundär',
                        'success' => 'Grün',
                        'warning' => 'Gelb',
                        'danger' => 'Rot',
                        'info' => 'Blau',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aktionen')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
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
            'index' => Pages\ListDocumentTypes::route('/'),
            'create' => Pages\CreateDocumentType::route('/create'),
            'edit' => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    // Zugriffskontrolle für System-Ressourcen (Manager und Administrator)
    public static function canViewAny(): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->isManagerOrAdmin() ?? false;
    }
}
