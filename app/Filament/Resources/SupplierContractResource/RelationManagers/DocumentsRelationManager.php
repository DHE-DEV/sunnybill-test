<?php

namespace App\Filament\Resources\SupplierContractResource\RelationManagers;

use App\Models\Document;
use App\Services\DocumentStorageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumente';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    protected static ?string $icon = 'heroicon-o-document';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dokument-Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Datei')
                            ->required()
                            ->disk(DocumentStorageService::getDiskName())
                            ->directory(DocumentStorageService::getUploadDirectory('supplier_contract'))
                            ->preserveFilenames()
                            ->maxSize(50 * 1024) // 50MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain',
                                'application/zip',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->label('Dokumentname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options(Document::getCategories())
                            ->default('contracts')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(1000),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category_label')
                    ->label('Kategorie')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Typ')
                    ->toggleable()
                    ->formatStateUsing(fn (string $state): string => strtoupper(pathinfo($state, PATHINFO_EXTENSION))),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(Document::getCategories()),
                Tables\Filters\Filter::make('uploaded_today')
                    ->label('Heute hochgeladen')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('uploaded_this_week')
                    ->label('Diese Woche hochgeladen')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Dokument hochladen')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['path']) && $data['path']) {
                            $metadata = DocumentStorageService::extractFileMetadata($data['path']);
                            $data = array_merge($data, $metadata);
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vorschau')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => $record->download_url)
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make()
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Dokument Details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('original_name')
                                    ->label('Originaler Dateiname')
                                    ->disabled(),
                                Forms\Components\TextInput::make('category_label')
                                    ->label('Kategorie')
                                    ->disabled(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->disabled(),
                                Forms\Components\TextInput::make('formatted_size')
                                    ->label('Dateigröße')
                                    ->disabled(),
                                Forms\Components\TextInput::make('mime_type')
                                    ->label('MIME-Typ')
                                    ->disabled(),
                                Forms\Components\TextInput::make('uploadedBy.name')
                                    ->label('Hochgeladen von')
                                    ->disabled(),
                                Forms\Components\TextInput::make('created_at')
                                    ->label('Hochgeladen am')
                                    ->disabled()
                                    ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
                            ])->columns(2),
                    ]),
                Tables\Actions\EditAction::make()
                    ->modalWidth('4xl')
                    ->form([
                        Forms\Components\Section::make('Dokument bearbeiten')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Dokumentname')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('category')
                                    ->label('Kategorie')
                                    ->options(Document::getCategories())
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(3)
                                    ->maxLength(1000),
                            ])->columns(2),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Dokumente vorhanden')
            ->emptyStateDescription('Laden Sie das erste Dokument für diesen Vertrag hoch.')
            ->emptyStateIcon('heroicon-o-document');
    }
}