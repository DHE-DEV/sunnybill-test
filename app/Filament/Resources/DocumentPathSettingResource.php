<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentPathSettingResource\Pages;
use App\Models\DocumentPathSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentPathSettingResource extends Resource
{
    protected static ?string $model = DocumentPathSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Dokumentpfade';

    protected static ?string $modelLabel = 'Dokumentpfad-Einstellung';

    protected static ?string $pluralModelLabel = 'Dokumentpfad-Einstellungen';

    protected static ?string $navigationGroup = 'Einstellungen';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pfadkonfiguration')
                    ->schema([
                        Forms\Components\Select::make('documentable_type')
                            ->label('Dokumenttyp')
                            ->options(DocumentPathSetting::getDocumentableTypes())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('placeholders', null)),

                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options(DocumentPathSetting::getCategories())
                            ->placeholder('Alle Kategorien (Standard)')
                            ->helperText('Leer lassen für Standard-Pfad dieses Dokumenttyps'),

                        Forms\Components\TextInput::make('path_template')
                            ->label('Pfad-Template')
                            ->required()
                            ->placeholder('z.B. lieferanten/{supplier_number}/vertraege/{contract_internal_number}')
                            ->helperText('Verwenden Sie Platzhalter in geschweiften Klammern: {placeholder}')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Beschreibung der Pfadkonfiguration')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dateinamen-Konfiguration')
                    ->description('Konfiguration für die Generierung von Dateinamen')
                    ->schema([
                        Forms\Components\Select::make('filename_strategy')
                            ->label('Dateinamen-Strategie')
                            ->options(DocumentPathSetting::getFilenameStrategies())
                            ->default('original')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== 'template') {
                                    $set('filename_template', null);
                                }
                            }),

                        Forms\Components\TextInput::make('filename_template')
                            ->label('Dateinamen-Template')
                            ->placeholder('z.B. {supplier_number}_{timestamp}')
                            ->helperText('Verfügbare Platzhalter werden basierend auf dem Dokumenttyp angezeigt')
                            ->visible(fn (callable $get) => $get('filename_strategy') === 'template'),

                        Forms\Components\TextInput::make('filename_prefix')
                            ->label('Dateinamen-Präfix')
                            ->placeholder('z.B. DOC_')
                            ->helperText('Text, der vor dem Dateinamen eingefügt wird'),

                        Forms\Components\TextInput::make('filename_suffix')
                            ->label('Dateinamen-Suffix')
                            ->placeholder('z.B. _backup')
                            ->helperText('Text, der vor der Dateierweiterung eingefügt wird'),

                        Forms\Components\Toggle::make('preserve_extension')
                            ->label('Dateierweiterung beibehalten')
                            ->default(true)
                            ->helperText('Behält die ursprüngliche Dateierweiterung bei'),

                        Forms\Components\Toggle::make('sanitize_filename')
                            ->label('Dateinamen bereinigen')
                            ->default(true)
                            ->helperText('Entfernt problematische Zeichen aus Dateinamen'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Verfügbare Platzhalter')
                    ->schema([
                        Forms\Components\Placeholder::make('available_placeholders')
                            ->label('')
                            ->content(function (callable $get) {
                                $documentableType = $get('documentable_type');
                                if (!$documentableType) {
                                    return 'Wählen Sie zuerst einen Dokumenttyp aus.';
                                }

                                $placeholders = DocumentPathSetting::getAvailablePlaceholders($documentableType);
                                
                                $html = '<div class="space-y-2">';
                                $html .= '<p class="text-sm font-medium text-gray-700">Verfügbare Platzhalter für diesen Dokumenttyp:</p>';
                                $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
                                
                                foreach ($placeholders as $placeholder => $description) {
                                    $html .= '<div class="flex items-center space-x-2 p-2 bg-gray-50 rounded">';
                                    $html .= '<code class="text-xs bg-gray-200 px-2 py-1 rounded">{' . $placeholder . '}</code>';
                                    $html .= '<span class="text-xs text-gray-600">' . $description . '</span>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Beispiele')
                    ->schema([
                        Forms\Components\Placeholder::make('examples')
                            ->label('')
                            ->content(function () {
                                $examples = [
                                    'Lieferanten (Standard)' => 'lieferanten/{supplier_number}',
                                    'Lieferanten-Verträge' => 'lieferanten/{supplier_number}/vertraege/{contract_internal_number}',
                                    'Kunden nach Jahr' => 'kunden/{year}/{customer_number}',
                                    'Solaranlagen mit Datum' => 'solaranlagen/{plant_number}/{year}-{month}',
                                    'Aufgaben nach Typ' => 'aufgaben/{task_number}',
                                    'Rechnungen nach Jahr' => 'rechnungen/{year}/{invoice_number}',
                                ];

                                $html = '<div class="space-y-2">';
                                $html .= '<p class="text-sm font-medium text-gray-700">Beispiel-Templates:</p>';
                                
                                foreach ($examples as $name => $template) {
                                    $html .= '<div class="flex items-center justify-between p-2 bg-blue-50 rounded">';
                                    $html .= '<span class="text-sm font-medium text-blue-900">' . $name . '</span>';
                                    $html .= '<code class="text-xs bg-blue-200 px-2 py-1 rounded text-blue-800">' . $template . '</code>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('documentable_type')
                    ->label('Dokumenttyp')
                    ->formatStateUsing(fn (string $state): string => DocumentPathSetting::getDocumentableTypes()[$state] ?? $state)
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->formatStateUsing(fn (?string $state): string => $state ? (DocumentPathSetting::getCategories()[$state] ?? $state) : 'Standard')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('path_template')
                    ->label('Pfad-Template')
                    ->fontFamily('mono')
                    ->copyable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->path_template),

                Tables\Columns\TextColumn::make('filename_strategy')
                    ->label('Dateinamen-Strategie')
                    ->formatStateUsing(fn (string $state): string => DocumentPathSetting::getFilenameStrategies()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'original' => 'gray',
                        'random' => 'warning',
                        'template' => 'success',
                        default => 'gray'
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('documentable_type')
                    ->label('Dokumenttyp')
                    ->options(DocumentPathSetting::getDocumentableTypes()),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(array_merge(['standard' => 'Standard'], DocumentPathSetting::getCategories()))
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'standard') {
                            return $query->whereNull('category');
                        }
                        return $query->where('category', $data['value']);
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('test_path')
                        ->label('Pfad testen')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->action(function ($record) {
                            // Hier könnte eine Test-Funktionalität implementiert werden
                            \Filament\Notifications\Notification::make()
                                ->title('Pfad-Test')
                                ->body('Test-Funktionalität wird in einer zukünftigen Version implementiert.')
                                ->info()
                                ->send();
                        }),
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
            ->defaultSort('documentable_type');
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
            'index' => Pages\ListDocumentPathSettings::route('/'),
            'create' => Pages\CreateDocumentPathSetting::route('/create'),
            'view' => Pages\ViewDocumentPathSetting::route('/{record}'),
            'edit' => Pages\EditDocumentPathSetting::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}