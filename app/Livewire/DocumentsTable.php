<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\SolarPlant;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use App\Services\DocumentStorageService;
use App\Services\DocumentUploadConfig;

class DocumentsTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public SolarPlant $solarPlant;

    public function mount(SolarPlant $solarPlant): void
    {
        $this->solarPlant = $solarPlant;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->where('documentable_type', 'App\Models\SolarPlant')
                    ->where('documentable_id', $this->solarPlant->id)
                    ->with(['uploadedBy', 'documentType'])
            )
            ->headerActions([
                Tables\Actions\Action::make('upload_document')
                    ->label('Dokument hochladen')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Dokumentname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Bauplan, Vertrag, Rechnung'),
                        Forms\Components\Select::make('document_type_id')
                            ->label('Dokumenttyp')
                            ->options(
                                \App\Models\DocumentType::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Dokumenttyp auswählen')
                            ->live(), // Macht das Feld reaktiv für Pfad-Vorschau
                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options([
                                'planning' => 'Planung',
                                'permits' => 'Genehmigungen',
                                'installation' => 'Installation',
                                'maintenance' => 'Wartung',
                                'invoices' => 'Rechnungen',
                                'certificates' => 'Zertifikate',
                                'contracts' => 'Verträge',
                                'correspondence' => 'Korrespondenz',
                                'technical' => 'Technische Unterlagen',
                                'photos' => 'Fotos',
                            ])
                            ->placeholder('Kategorie auswählen')
                            ->live(), // Macht das Feld reaktiv für Pfad-Vorschau
                        Forms\Components\Placeholder::make('path_preview')
                            ->label('Speicherort')
                            ->content(function (Forms\Get $get): string {
                                // Hole Kategorie und Dokumenttyp
                                $category = $get('category');
                                $documentTypeId = $get('document_type_id');
                                
                                // Basis-Pfad mit Solaranlage
                                $solarPlant = $this->solarPlant;
                                $basePath = 'solaranlagen/' . $solarPlant->plant_number;
                                
                                // Füge Dokumenttyp-Pfad hinzu wenn vorhanden
                                if ($documentTypeId) {
                                    try {
                                        $documentType = \App\Models\DocumentType::find($documentTypeId);
                                        if ($documentType && $documentType->key) {
                                            $basePath .= '/' . $documentType->key;
                                        }
                                    } catch (\Exception $e) {
                                        // Ignore error
                                    }
                                }
                                
                                // Füge Kategorie-Pfad hinzu wenn vorhanden
                                if ($category) {
                                    $basePath .= '/' . $category;
                                }
                                
                                // Konvertiere zu Windows-Pfad-Format für bessere Lesbarkeit
                                $windowsPath = str_replace('/', '\\', $basePath);
                                
                                return "📁 {$windowsPath}\\";
                            })
                            ->helperText(function (Forms\Get $get): string {
                                $category = $get('category');
                                $documentTypeId = $get('document_type_id');
                                $documentTypeName = null;
                                $documentTypeKey = null;
                                
                                // Hole Dokumenttyp-Informationen wenn vorhanden
                                if ($documentTypeId) {
                                    try {
                                        $documentType = \App\Models\DocumentType::find($documentTypeId);
                                        $documentTypeName = $documentType?->name;
                                        $documentTypeKey = $documentType?->key;
                                    } catch (\Exception $e) {
                                        // Ignore error
                                    }
                                }
                                
                                $baseHelperText = 'Hier wird das Dokument gespeichert.';
                                $details = [];
                                
                                // Zeige Dokumenttyp-Info wenn vorhanden
                                if ($documentTypeName) {
                                    $details[] = "Dokumenttyp: {$documentTypeName} (Pfad: {$documentTypeKey})";
                                }
                                
                                // Zeige Kategorie
                                if ($category) {
                                    $details[] = "Kategorie: {$category}";
                                }
                                
                                if (!empty($details)) {
                                    return $baseHelperText . ' | ' . implode(' | ', $details);
                                }
                                
                                return $baseHelperText . ' Der Pfad wird aus Solaranlage, Dokumenttyp und Kategorie zusammengesetzt.';
                            })
                            ->live() // Aktiviert Live-Updates
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('file')
                            ->label('Datei')
                            ->required()
                            ->maxSize(10240) // 10MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/*',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/zip',
                                'text/plain',
                                'text/csv',
                            ])
                            ->helperText('Maximale Dateigröße: 10 MB. Erlaubte Formate: PDF, Bilder, Word, Excel, ZIP, Text, CSV')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames()
                            ->storeFileNamesIn('original_name'),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->placeholder('Optionale Beschreibung des Dokuments'),
                        Forms\Components\Toggle::make('is_favorite')
                            ->label('Als Favorit markieren')
                            ->default(false)
                            ->helperText('Favorisierte Dokumente werden hervorgehoben angezeigt'),
                    ])
                    ->action(function (array $data) {
                        // Upload-Funktion
                        $file = $data['file'];
                        
                        // Baue den Pfad aus Solaranlage, Dokumenttyp und Kategorie
                        $pathParts = ['solaranlagen', $this->solarPlant->plant_number];
                        
                        // Füge Dokumenttyp-Pfad hinzu
                        if (isset($data['document_type_id'])) {
                            $documentType = \App\Models\DocumentType::find($data['document_type_id']);
                            if ($documentType && $documentType->key) {
                                $pathParts[] = $documentType->key;
                            }
                        }
                        
                        // Füge Kategorie-Pfad hinzu
                        if (isset($data['category'])) {
                            $pathParts[] = $data['category'];
                        }
                        
                        $directory = implode('/', $pathParts);
                        
                        // Speichere die Datei im zusammengesetzten Verzeichnis
                        $path = $file->store($directory, 'documents');
                        
                        // Erstelle den Dokument-Eintrag
                        $document = new Document();
                        $document->name = $data['name'];
                        $document->original_name = $file->getClientOriginalName();
                        $document->path = $path;
                        $document->disk = 'documents';
                        $document->mime_type = $file->getMimeType();
                        $document->size = $file->getSize();
                        $document->category = $data['category'] ?? null;
                        $document->document_type_id = $data['document_type_id'] ?? null;
                        $document->description = $data['description'] ?? null;
                        $document->documentable_type = 'App\Models\SolarPlant';
                        $document->documentable_id = $this->solarPlant->id;
                        $document->uploaded_by = auth()->id();
                        $document->is_favorite = $data['is_favorite'] ?? false;
                        $document->save();
                        
                        Notification::make()
                            ->title('Dokument hochgeladen')
                            ->body('Das Dokument wurde erfolgreich hochgeladen.')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Neues Dokument hochladen')
                    ->modalSubmitActionLabel('Dokument hochladen')
                    ->modalWidth('lg'),
            ])
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->icon(fn ($record) => $record->icon)
                    ->color('primary')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Dokumentname')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(true)
                    ->limit(50),

                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Dokumenttyp')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Nicht angegeben'),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->state(fn ($record) => $record->formatted_size)
                    ->alignEnd()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Dateityp')
                    ->state(fn ($record) => match(true) {
                        str_contains($record->mime_type, 'pdf') => 'PDF',
                        str_contains($record->mime_type, 'image/jpeg') => 'JPEG',
                        str_contains($record->mime_type, 'image/png') => 'PNG',
                        str_contains($record->mime_type, 'image/gif') => 'GIF',
                        str_contains($record->mime_type, 'image/') => 'Bild',
                        str_contains($record->mime_type, 'wordprocessingml') => 'Word',
                        str_contains($record->mime_type, 'spreadsheetml') => 'Excel',
                        str_contains($record->mime_type, 'presentationml') => 'PowerPoint',
                        str_contains($record->mime_type, 'zip') => 'ZIP',
                        str_contains($record->mime_type, 'rar') => 'RAR',
                        str_contains($record->mime_type, 'text/plain') => 'Text',
                        str_contains($record->mime_type, 'text/csv') => 'CSV',
                        default => strtoupper(pathinfo($record->original_name, PATHINFO_EXTENSION)) ?: 'Unbekannt',
                    })
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        str_contains($record->mime_type, 'pdf') => 'danger',
                        str_contains($record->mime_type, 'image/') => 'success',
                        str_contains($record->mime_type, 'wordprocessingml') => 'info',
                        str_contains($record->mime_type, 'spreadsheetml') => 'warning',
                        str_contains($record->mime_type, 'zip') || str_contains($record->mime_type, 'rar') => 'gray',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorit')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('Unbekannt')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Original-Dateiname')
                    ->searchable()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Dateityp')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->date('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'planning' => 'Planung',
                        'permits' => 'Genehmigungen',
                        'installation' => 'Installation',
                        'maintenance' => 'Wartung',
                        'invoices' => 'Rechnungen',
                        'certificates' => 'Zertifikate',
                        'contracts' => 'Verträge',
                        'correspondence' => 'Korrespondenz',
                        'technical' => 'Technische Unterlagen',
                        'photos' => 'Fotos',
                    ]),

                Tables\Filters\SelectFilter::make('document_type_id')
                    ->label('Dokumenttyp')
                    ->relationship('documentType', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_favorite')
                    ->label('Favorit')
                    ->placeholder('Alle Dokumente')
                    ->trueLabel('Nur Favoriten')
                    ->falseLabel('Nicht favorisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_favorite', true),
                        false: fn (Builder $query) => $query->where('is_favorite', false),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\SelectFilter::make('mime_type')
                    ->label('Dateityp')
                    ->options([
                        'application/pdf' => 'PDF',
                        'image/jpeg' => 'JPEG Bild',
                        'image/png' => 'PNG Bild',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Dokument',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Tabelle',
                        'application/zip' => 'ZIP Archiv',
                        'text/plain' => 'Text Datei',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->label('Upload-Datum')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Von')
                            ->placeholder('Upload-Datum von'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bis')
                            ->placeholder('Upload-Datum bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('recent')
                    ->label('Kürzlich hochgeladen')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('created_at', '>=', now()->subDays(30))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => $record->url)
                        ->openUrlInNewTab(true),
                    
                    Tables\Actions\Action::make('download')
                        ->label('Herunterladen')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn ($record) => $record->download_url)
                        ->openUrlInNewTab(true),

                    Tables\Actions\Action::make('toggle_favorite')
                        ->label(fn ($record) => $record->is_favorite ? 'Favorit entfernen' : 'Als Favorit markieren')
                        ->icon(fn ($record) => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                        ->color(fn ($record) => $record->is_favorite ? 'warning' : 'gray')
                        ->action(function ($record) {
                            $record->update(['is_favorite' => !$record->is_favorite]);
                        }),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_favorite')
                        ->label('Als Favorit markieren')
                        ->icon('heroicon-s-star')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_favorite' => true]);
                            });
                        }),

                    Tables\Actions\BulkAction::make('unmark_favorite')
                        ->label('Favorit entfernen')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_favorite' => false]);
                            });
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Ausgewählte löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Dokumente löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Dokumente löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden und die Dateien werden dauerhaft von der Festplatte entfernt.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->successNotificationTitle('Dokumente gelöscht'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Keine Dokumente vorhanden')
            ->emptyStateDescription('Es wurden noch keine Dokumente zu dieser Solaranlage hochgeladen.')
            ->emptyStateIcon('heroicon-o-folder')
            ->poll('30s'); // Automatische Aktualisierung alle 30 Sekunden
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getTableName(): string
    {
        return 'documents-table-' . $this->solarPlant->id;
    }

    public function render(): View
    {
        return view('livewire.documents-table');
    }
}
