<?php

namespace App\Services;

use App\Models\Document;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

/**
 * Service zum dynamischen Erstellen von Dokumenten-Tabellen
 */
class DocumentTableBuilder
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function make(array $config = []): self
    {
        return new self($config);
    }

    /**
     * Erstellt die Tabelle basierend auf der Konfiguration
     */
    public function build(Table $table): Table
    {
        return $table
            ->recordTitleAttribute($this->config('recordTitleAttribute', 'name'))
            ->columns($this->getColumns())
            ->filters($this->getFilters())
            ->headerActions($this->getHeaderActions())
            ->actions($this->getActions())
            ->bulkActions($this->getBulkActions())
            ->defaultSort(...$this->config('defaultSort', ['created_at', 'desc']))
            ->emptyStateHeading($this->config('emptyStateHeading', 'Keine Dokumente vorhanden'))
            ->emptyStateDescription($this->config('emptyStateDescription', 'Fügen Sie das erste Dokument hinzu.'))
            ->emptyStateIcon($this->config('emptyStateIcon', 'heroicon-o-document'));
    }

    /**
     * Erstellt die Tabellen-Spalten
     */
    protected function getColumns(): array
    {
        $columns = [];

        // Icon Spalte
        if ($this->config('showIcon', true)) {
            $columns[] = Tables\Columns\IconColumn::make('icon')
                ->label('')
                ->icon(fn ($record) => $this->getDocumentIcon($record))
                ->color('primary');
        }

        // Name Spalte
        $columns[] = Tables\Columns\TextColumn::make('name')
            ->label($this->config('nameLabel', 'Name'))
            ->searchable($this->config('nameSearchable', true))
            ->sortable($this->config('nameSortable', true))
            ->weight($this->config('nameWeight', 'bold'))
            ->description(fn ($record) => $this->config('showDescription', false) ? $record->description : null);

        // DocumentType Spalte (neue Implementation)
        if ($this->config('showCategory', true)) {
            $columns[] = $this->createDocumentTypeColumn();
        }
        
        // Legacy Kategorie Spalte (für Rückwärtskompatibilität)
        if ($this->config('categories') && $this->config('showLegacyCategory', false)) {
            $columns[] = $this->createCategoryColumn();
        }

        // Größe Spalte
        if ($this->config('showSize', true)) {
            $columns[] = Tables\Columns\TextColumn::make('formatted_size')
                ->label($this->config('sizeLabel', 'Größe'))
                ->alignRight()
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('size', $direction);
                });
        }

        // Dateityp Spalte (wie in admin/documents)
        if ($this->config('showFileType', true)) {
            $columns[] = Tables\Columns\TextColumn::make('mime_type')
                ->label($this->config('fileTypeLabel', 'Dateityp'))
                ->formatStateUsing(fn (string $state): string => match (true) {
                    str_contains($state, 'pdf') => 'PDF',
                    str_contains($state, 'image/jpeg') || str_contains($state, 'image/jpg') => 'JPEG',
                    str_contains($state, 'image/png') => 'PNG',
                    str_contains($state, 'image/gif') => 'GIF',
                    str_contains($state, 'image') => 'Bild',
                    str_contains($state, 'word') || str_contains($state, 'document') => 'Word',
                    str_contains($state, 'excel') || str_contains($state, 'spreadsheet') => 'Excel',
                    str_contains($state, 'zip') => 'ZIP',
                    str_contains($state, 'rar') => 'RAR',
                    default => strtoupper(pathinfo($state, PATHINFO_EXTENSION) ?: 'Unbekannt'),
                })
                ->badge()
                ->color(fn (string $state): string => match (true) {
                    str_contains($state, 'pdf') => 'danger',
                    str_contains($state, 'image') => 'success',
                    str_contains($state, 'word') || str_contains($state, 'document') => 'info',
                    str_contains($state, 'excel') || str_contains($state, 'spreadsheet') => 'warning',
                    str_contains($state, 'zip') || str_contains($state, 'rar') => 'gray',
                    default => 'primary',
                })
                ->sortable();
        }

        // MIME-Type Spalte (optional)
        if ($this->config('showMimeType', false)) {
            $columns[] = Tables\Columns\TextColumn::make('mime_type')
                ->label($this->config('mimeTypeLabel', 'MIME-Type'))
                ->toggleable($this->config('mimeTypeToggleable', true))
                ->formatStateUsing(fn (string $state): string =>
                    strtoupper(pathinfo($state, PATHINFO_EXTENSION))
                );
        }

        // Hochgeladen von Spalte
        if ($this->config('showUploadedBy', true)) {
            $columns[] = Tables\Columns\TextColumn::make('uploadedBy.name')
                ->label($this->config('uploadedByLabel', 'Hochgeladen von'))
                ->sortable($this->config('uploadedBySortable', true))
                ->toggleable($this->config('uploadedByToggleable', true));
        }

        // Speicherort Spalte
        if ($this->config('showStoragePath', true)) {
            $columns[] = Tables\Columns\TextColumn::make('path')
                ->label($this->config('storagePathLabel', 'Speicherort'))
                ->formatStateUsing(function (?string $state): string {
                    if (!$state) return '-';
                    
                    // Zeige nur den Ordnerpfad ohne Dateiname
                    $directory = dirname($state);
                    
                    // Entferne führende Slashes und zeige relativen Pfad
                    $directory = ltrim($directory, '/\\');
                    
                    // Wenn es der Root-Ordner ist, zeige "Root"
                    if ($directory === '.' || $directory === '') {
                        return 'Root';
                    }
                    
                    return $directory;
                })
                ->copyable()
                ->copyMessage('Speicherort kopiert')
                ->tooltip(fn (?string $state): string => $state ? "Vollständiger Pfad: {$state}" : 'Kein Pfad verfügbar')
                ->sortable($this->config('storagePathSortable', true))
                ->toggleable($this->config('storagePathToggleable', true))
                ->toggledHiddenByDefault($this->config('storagePathHiddenByDefault', true))
                ->searchable($this->config('storagePathSearchable', true));
        }

        // Erstellt am Spalte
        if ($this->config('showCreatedAt', true)) {
            $columns[] = Tables\Columns\TextColumn::make('created_at')
                ->label($this->config('createdAtLabel', 'Erstellt'))
                ->dateTime($this->config('dateTimeFormat', 'd.m.Y H:i'))
                ->sortable($this->config('createdAtSortable', true))
                ->toggleable($this->config('createdAtToggleable', true));
        }

        return $columns;
    }

    /**
     * Erstellt die DocumentType-Spalte
     */
    protected function createDocumentTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('documentType.name')
            ->label($this->config('categoryLabel', 'Typ'))
            ->badge($this->config('categoryBadge', true))
            ->placeholder('Kein Typ')
            ->color(fn ($record): string => $this->getDocumentTypeColor($record))
            ->sortable($this->config('categorySortable', true))
            ->searchable();
    }

    /**
     * Erstellt die Kategorie-Spalte (Legacy)
     */
    protected function createCategoryColumn(): Tables\Columns\TextColumn
    {
        $categories = $this->config('categories', []);
        
        return Tables\Columns\TextColumn::make('category')
            ->label($this->config('categoryLabel', 'Kategorie'))
            ->badge($this->config('categoryBadge', true))
            ->formatStateUsing(fn (string $state): string => $categories[$state] ?? $state)
            ->color(fn (string $state): string => $this->getCategoryColor($state))
            ->sortable($this->config('categorySortable', true));
    }

    /**
     * Erstellt die Filter
     */
    protected function getFilters(): array
    {
        $filters = [];

        // DocumentType Filter
        if ($this->config('showCategory', true) && $this->config('enableCategoryFilter', true)) {
            $filters[] = Tables\Filters\SelectFilter::make('document_type_id')
                ->label($this->config('categoryFilterLabel', 'Typ'))
                ->relationship('documentType', 'name')
                ->searchable()
                ->preload();
        }

        // Legacy Kategorie Filter
        if ($this->config('categories') && $this->config('enableLegacyCategoryFilter', false)) {
            $filters[] = Tables\Filters\SelectFilter::make('category')
                ->label($this->config('categoryFilterLabel', 'Kategorie'))
                ->options($this->config('categories', []));
        }

        // Datum Filter
        if ($this->config('enableDateFilters', true)) {
            $filters[] = Tables\Filters\Filter::make('uploaded_today')
                ->label($this->config('todayFilterLabel', 'Heute hochgeladen'))
                ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()));

            $filters[] = Tables\Filters\Filter::make('uploaded_this_week')
                ->label($this->config('weekFilterLabel', 'Diese Woche hochgeladen'))
                ->query(fn (Builder $query): Builder => 
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                );
        }

        // Custom Filter
        if ($customFilters = $this->config('customFilters', [])) {
            $filters = array_merge($filters, $customFilters);
        }

        return $filters;
    }

    /**
     * Erstellt die Header-Aktionen
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->config('enableCreate', true)) {
            // Verwende eine benutzerdefinierte Action statt CreateAction
            // Diese funktioniert auch im View-Modus
            $actions[] = Tables\Actions\Action::make('create_document')
                ->label($this->config('createButtonLabel', 'Dokument hinzufügen'))
                ->icon($this->config('createButtonIcon', 'heroicon-o-plus'))
                ->button()
                ->color('primary')
                ->modalWidth($this->config('modalWidth', '4xl'))
                ->modalHeading('Dokument hinzufügen')
                ->form(function () {
                    // Verwende das Formular aus DocumentFormBuilder
                    return DocumentFormBuilder::make($this->config)->getFormSchema();
                })
                ->action(function (array $data, $livewire): void {
                    // Verarbeite die Upload-Daten
                    $data = $this->processUploadData($data);
                    
                    // Erstelle das Dokument über die Relation
                    $relationship = $livewire->getRelationship();
                    $record = $relationship->create($data);
                    
                    // Benachrichtigung
                    \Filament\Notifications\Notification::make()
                        ->title('Dokument erfolgreich hochgeladen')
                        ->success()
                        ->send();
                })
                ->visible(function ($livewire): bool {
                    // Zeige die Action immer an, auch im View-Modus, wenn der User berechtigt ist
                    $hasPermission = auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
                    
                    // Zusätzlich prüfen ob es ein RelationManager ist und canCreate implementiert
                    if (method_exists($livewire, 'canCreate')) {
                        return $livewire->canCreate() && $hasPermission;
                    }
                    
                    return $hasPermission;
                });
        }

        // Custom Header Actions
        if ($customActions = $this->config('customHeaderActions', [])) {
            $actions = array_merge($actions, $customActions);
        }

        return $actions;
    }

    /**
     * Erstellt die Zeilen-Aktionen
     */
    protected function getActions(): array
    {
        $actions = [];

        // Vorschau Aktion
        if ($this->config('showPreview', true)) {
            $actions[] = Tables\Actions\Action::make('preview')
                ->label($this->config('previewLabel', 'Vorschauuuu'))
                ->icon($this->config('previewIcon', 'heroicon-o-eye'))
                ->color($this->config('previewColor', 'info'))
                ->url(fn (Document $record): string => $record->url)
                ->openUrlInNewTab()
                ->visible(function (Document $record): bool {
                    // Zeige Vorschau nur für Bilder und PDF-Dateien an
                    if (empty($record->path) || empty($record->mime_type)) {
                        return false;
                    }
                    
                    $mimeType = strtolower($record->mime_type);
                    
                    // PDF-Dateien
                    if (str_contains($mimeType, 'pdf')) {
                        return true;
                    }
                    
                    // Bild-Dateien
                    if (str_contains($mimeType, 'image/')) {
                        return true;
                    }
                    
                    return false;
                });
        }

        // Download Aktion
        if ($this->config('showDownload', true)) {
            $actions[] = Tables\Actions\Action::make('download')
                ->label($this->config('downloadLabel', 'Download'))
                ->icon($this->config('downloadIcon', 'heroicon-o-arrow-down-tray'))
                ->color($this->config('downloadColor', 'success'))
                ->url(fn (Document $record): string => $record->download_url)
                ->openUrlInNewTab();
        }

        // Standard CRUD Aktionen
        if ($this->config('showView', true)) {
            $actions[] = Tables\Actions\ViewAction::make()
                ->modalWidth($this->config('modalWidth', '4xl'))
                ->form($this->getViewForm())
                ->visible(fn ($record, $livewire): bool =>
                    // Prüfe Berechtigung über das Trait oder erlaube allen
                    method_exists($livewire, 'canView')
                        ? $livewire->canView($record)
                        : true
                );
        }

        if ($this->config('showEdit', true)) {
            $actions[] = Tables\Actions\EditAction::make()
                ->modalWidth($this->config('modalWidth', '4xl'))
                ->form($this->getEditForm())
                ->visible(fn ($record, $livewire): bool =>
                    // Temporär für alle Benutzer aktiviert zum Testen
                    true
                );
        }

        if ($this->config('showDelete', true)) {
            $actions[] = Tables\Actions\DeleteAction::make()
                ->visible(fn ($record, $livewire): bool =>
                    // Temporär für alle Benutzer aktiviert zum Testen
                    true
                );
        }

        // Gruppiere Aktionen in einer "Aktionen"-Schaltfläche
        if (count($actions) > 1) {
            return [
                Tables\Actions\ActionGroup::make($actions)
                    ->label($this->config('actionsLabel', 'Aktionen'))
                    ->icon($this->config('actionsIcon', 'heroicon-m-ellipsis-vertical'))
                    ->size($this->config('actionsSize', 'sm'))
                    ->color($this->config('actionsColor', 'gray'))
                    ->button()
            ];
        }

        return $actions;
    }

    /**
     * Erstellt die Bulk-Aktionen
     */
    protected function getBulkActions(): array
    {
        $actions = [];

        if ($this->config('enableBulkActions', true)) {
            if ($this->config('enableBulkDelete', true)) {
                $actions[] = Tables\Actions\DeleteBulkAction::make();
            }

            // Custom Bulk Actions
            if ($customBulkActions = $this->config('customBulkActions', [])) {
                $actions = array_merge($actions, $customBulkActions);
            }
        }

        return empty($actions) ? [] : [Tables\Actions\BulkActionGroup::make($actions)];
    }

    /**
     * Erstellt das View-Formular
     */
    protected function getViewForm(): array
    {
        return DocumentFormBuilder::make(array_merge($this->config, [
            'required' => false,
            'showSection' => true,
            'sectionTitle' => 'Dokument Details',
            'showStoragePath' => true, // Zeige Speicherort im View-Modal
            'storagePathLabel' => 'Speicherort'
        ]))->getFormSchema();
    }

    /**
     * Erstellt das Edit-Formular
     */
    protected function getEditForm(): array
    {
        return DocumentFormBuilder::make(array_merge($this->config, [
            'showSection' => true,
            'sectionTitle' => 'Dokument bearbeiten'
        ]))->getFormSchema();
    }

    /**
     * Verarbeitet Upload-Daten
     */
    protected function processUploadData(array $data): array
    {
        if (isset($data['path']) && $data['path']) {
            // FileUpload gibt ein Array zurück, nimm die erste Datei
            $filePath = is_array($data['path']) ? $data['path'][0] ?? null : $data['path'];
            
            if ($filePath) {
                try {
                    $metadata = DocumentStorageService::extractFileMetadata($filePath);
                    
                    // Merge Metadaten, aber behalte den ursprünglichen path
                    $data = array_merge($data, $metadata);
                    $data['path'] = $filePath; // Stelle sicher, dass path ein String ist
                } catch (\Exception $e) {
                    \Log::error('Fehler beim Extrahieren der Metadaten in DocumentTableBuilder', [
                        'file_path' => $filePath,
                        'error' => $e->getMessage(),
                        'original_path' => $data['path']
                    ]);
                }
            }
        }

        return $data;
    }

    /**
     * Bestimmt das Icon für ein Dokument
     */
    protected function getDocumentIcon($record): string
    {
        $mimeType = $record->mime_type ?? '';
        
        if (str_contains($mimeType, 'pdf')) {
            return 'heroicon-o-document-text';
        } elseif (str_contains($mimeType, 'image')) {
            return 'heroicon-o-photo';
        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'heroicon-o-document';
        } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return 'heroicon-o-table-cells';
        } elseif (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar')) {
            return 'heroicon-o-archive-box';
        }

        return 'heroicon-o-document';
    }

    /**
     * Bestimmt die Farbe für einen DocumentType
     */
    protected function getDocumentTypeColor($record): string
    {
        if (!$record->documentType) {
            return 'gray';
        }

        $slug = $record->documentType->slug;
        
        $colors = $this->config('documentTypeColors', [
            'contract' => 'success',
            'invoice' => 'warning',
            'certificate' => 'info',
            'manual' => 'gray',
            'photo' => 'purple',
            'plan' => 'blue',
            'report' => 'orange',
            'correspondence' => 'green',
            'commissioning' => 'cyan',
            'legal' => 'red',
            'other' => 'gray',
        ]);

        return $colors[$slug] ?? 'gray';
    }

    /**
     * Bestimmt die Farbe für eine Kategorie (Legacy)
     */
    protected function getCategoryColor(string $category): string
    {
        $colors = $this->config('categoryColors', [
            'contract' => 'success',
            'invoice' => 'warning',
            'certificate' => 'info',
            'manual' => 'gray',
            'photo' => 'purple',
            'plan' => 'blue',
            'report' => 'orange',
            'correspondence' => 'green',
            'other' => 'gray',
        ]);

        return $colors[$category] ?? 'gray';
    }

    /**
     * Hilfsmethode zum Abrufen von Konfigurationswerten
     */
    protected function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}