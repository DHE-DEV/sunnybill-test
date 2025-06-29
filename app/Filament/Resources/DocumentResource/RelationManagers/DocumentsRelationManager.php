<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Dokumente';

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('Datei')
                    ->required()
                    ->disk('local')
                    ->directory('documents')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/jpg',
                        'image/png',
                        'image/gif',
                        'application/zip',
                        'application/x-rar-compressed',
                    ])
                    ->maxSize(10240), // 10MB

                Forms\Components\TextInput::make('name')
                    ->label('Dokumentname')
                    ->required()
                    ->maxLength(255)
                    ->default(fn ($get) => $get('original_name')),

                Forms\Components\Select::make('category')
                    ->label('Kategorie')
                    ->options([
                        'contract' => 'Vertrag',
                        'invoice' => 'Rechnung',
                        'certificate' => 'Zertifikat',
                        'manual' => 'Handbuch',
                        'photo' => 'Foto',
                        'plan' => 'Plan/Zeichnung',
                        'report' => 'Bericht',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                    ])
                    ->searchable(),

                Forms\Components\Textarea::make('description')
                    ->label('Beschreibung')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'contract' => 'Vertrag',
                        'invoice' => 'Rechnung',
                        'certificate' => 'Zertifikat',
                        'manual' => 'Handbuch',
                        'photo' => 'Foto',
                        'plan' => 'Plan/Zeichnung',
                        'report' => 'Bericht',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'contract' => 'success',
                        'invoice' => 'warning',
                        'certificate' => 'info',
                        'manual' => 'gray',
                        'photo' => 'purple',
                        'plan' => 'blue',
                        'report' => 'orange',
                        'correspondence' => 'green',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('size', $direction);
                    }),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'contract' => 'Vertrag',
                        'invoice' => 'Rechnung',
                        'certificate' => 'Zertifikat',
                        'manual' => 'Handbuch',
                        'photo' => 'Foto',
                        'plan' => 'Plan/Zeichnung',
                        'report' => 'Bericht',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Dokument hinzufügen'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Document $record): string => route('documents.preview', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => str_contains($record->mime_type, 'image') || str_contains($record->mime_type, 'pdf')),
                    
                    Tables\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Document $record): string => route('documents.download', $record))
                        ->openUrlInNewTab(),

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
            ->emptyStateHeading('Keine Dokumente vorhanden')
            ->emptyStateDescription('Fügen Sie das erste Dokument hinzu.')
            ->emptyStateIcon('heroicon-o-document');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Wenn eine Datei hochgeladen wurde, extrahiere die Metadaten
        if (isset($data['path']) && $data['path']) {
            $filePath = $data['path'];
            $disk = 'local'; // Standard-Disk
            
            // Prüfe ob die Datei existiert
            if (Storage::disk($disk)->exists($filePath)) {
                // Extrahiere den ursprünglichen Dateinamen aus dem Pfad
                $originalName = basename($filePath);
                
                // Hole die Dateigröße
                $size = Storage::disk($disk)->size($filePath);
                
                // Bestimme den MIME-Type basierend auf der Dateierweiterung
                $mimeType = Storage::disk($disk)->mimeType($filePath);
                
                // Füge die fehlenden Felder hinzu
                $data['original_name'] = $originalName;
                $data['disk'] = $disk;
                $data['size'] = $size;
                $data['mime_type'] = $mimeType;
                $data['uploaded_by'] = auth()->id();
            }
        }

        return $data;
    }
}