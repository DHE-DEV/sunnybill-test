<?php

namespace App\Filament\Resources\SupplierContractBillingResource\RelationManagers;

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dokumentdetails')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Anbieter-Rechnung März 2025'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Optionale Beschreibung des Dokuments'),

                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options([
                                'invoice' => 'Rechnung',
                                'contract' => 'Vertrag',
                                'correspondence' => 'Korrespondenz',
                                'other' => 'Sonstiges',
                            ])
                            ->default('invoice')
                            ->required(),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('Datei')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->disk(DocumentStorageService::getDiskName())
                            ->directory(function () {
                                $record = $this->getOwnerRecord();
                                if ($record && $record->supplierContract) {
                                    $contractNumber = $record->supplierContract->contract_number ?? 'unbekannt';
                                    return DocumentStorageService::getUploadDirectory('supplier_contract_billing', [
                                        'contract_number' => $contractNumber
                                    ]);
                                }
                                return DocumentStorageService::getUploadDirectory('supplier_contract_billing');
                            })
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull()
                            ->visibility('private'),

                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Als Favorit markieren')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ViewColumn::make('preview')
                    ->label('Vorschau')
                    ->view('filament.tables.columns.document-thumbnail')
                    ->width('80px'),

                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorit')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(40)
                    ->description(function (Document $record): ?string {
                        if ($record->description) {
                            return \Illuminate\Support\Str::limit($record->description, 60);
                        }
                        return null;
                    })
                    ->tooltip(function (Document $record): ?string {
                        return $record->description;
                    }),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'invoice' => 'Rechnung',
                        'contract' => 'Vertrag',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'invoice',
                        'success' => 'contract',
                        'info' => 'correspondence',
                        'secondary' => 'other',
                    ]),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Dateiname')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->original_name),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Dateigröße')
                    ->getStateUsing(function ($record) {
                        $bytes = $record->size;
                        $units = ['B', 'KB', 'MB', 'GB'];
                        
                        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                            $bytes /= 1024;
                        }
                        
                        return round($bytes, 2) . ' ' . $units[$i];
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Dateityp')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'application/pdf' => 'PDF',
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'application/msword' => 'DOC',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
                        default => strtoupper(explode('/', $state)[1] ?? $state),
                    })
                    ->color(fn (string $state): string => match($state) {
                        'application/pdf' => 'danger',
                        'image/jpeg', 'image/png' => 'success',
                        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'invoice' => 'Rechnung',
                        'contract' => 'Vertrag',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                    ]),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favoriten')
                    ->placeholder('Alle Dokumente')
                    ->trueLabel('Nur Favoriten')
                    ->falseLabel('Keine Favoriten'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Dokument hinzufügen')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Set documentable relationship
                        $data['documentable_type'] = \App\Models\SupplierContractBilling::class;
                        $data['documentable_id'] = $this->getOwnerRecord()->id;
                        
                        // Handle file upload data
                        if (isset($data['file_path']) && $data['file_path']) {
                            $filePath = $data['file_path'];
                            $data['path'] = $filePath;
                            
                            // Extract file metadata using DocumentStorageService
                            $metadata = DocumentStorageService::extractFileMetadata($filePath);
                            $data = array_merge($data, $metadata);
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Herunterladen')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record): string => route('documents.download', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('preview')
                    ->label('Vorschau')
                    ->icon('heroicon-o-magnifying-glass')
                    ->modalHeading(fn (Document $record): string => 'Vorschau: ' . $record->name)
                    ->modalContent(function (Document $record): \Illuminate\Contracts\View\View {
                        return view('filament.modals.document-preview', [
                            'document' => $record,
                            'fileUrl' => route('documents.view', $record),
                        ]);
                    })
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->visible(fn (Document $record): bool => in_array($record->mime_type, ['application/pdf', 'image/jpeg', 'image/png'])),

                Tables\Actions\Action::make('view')
                    ->label('In neuem Tab öffnen')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Document $record): string => route('documents.view', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => in_array($record->mime_type, ['application/pdf', 'image/jpeg', 'image/png'])),

                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),

                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->reorderable('sort_order')
            ->striped()
            ->emptyStateHeading('Keine Dokumente vorhanden')
            ->emptyStateDescription('Laden Sie Dokumente wie Rechnungen oder Verträge hoch, um sie hier zu verwalten.')
            ->emptyStateIcon('heroicon-o-document-plus');
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}