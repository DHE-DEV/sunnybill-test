<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\DocumentPathSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dokumentinformationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('document_type_id')
                            ->label('Dokumententyp')
                            ->options(\App\Models\DocumentType::getSelectOptions())
                            ->searchable()
                            ->required()
                            ->placeholder('Dokumententyp auswählen...')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required(),
                                Forms\Components\TextInput::make('key')
                                    ->label('Schlüssel')
                                    ->required(),
                                Forms\Components\Select::make('color')
                                    ->label('Farbe')
                                    ->options([
                                        'gray' => 'Grau',
                                        'primary' => 'Primär',
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
                                    ])
                                    ->default('heroicon-o-document')
                                    ->required(),
                            ]),

                        Forms\Components\FileUpload::make('path')
                            ->label('Datei')
                            ->disk('documents')
                            ->directory(function (callable $get) {
                                // Neue konfigurierbare Verzeichnisstruktur
                                $type = $get('documentable_type');
                                $documentableId = $get('documentable_id');
                                $category = $get('category');
                                
                                if (!$type || !$documentableId) {
                                    return 'allgemein/' . date('Y');
                                }
                                
                                return static::getConfigurableDirectory($type, $documentableId, $category);
                            })
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                // Konfigurierbare Dateinamen-Generierung
                                $type = $get('documentable_type');
                                $documentableId = $get('documentable_id');
                                $category = $get('category');
                                
                                if (!$type || !$documentableId) {
                                    // Fallback für unvollständige Daten
                                    return $file->getClientOriginalName();
                                }
                                
                                return static::generateConfigurableFilename(
                                    $file->getClientOriginalName(),
                                    $type,
                                    $documentableId,
                                    $category
                                );
                            })
                            ->storeFileNamesIn('original_name')
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
                            ->maxSize(51200) // 50MB
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zuordnung')
                    ->schema([
                        Forms\Components\Select::make('documentable_type')
                            ->label('Dokumenttyp')
                            ->options([
                                'App\Models\SolarPlant' => 'Solaranlage',
                                'App\Models\Customer' => 'Kunde',
                                'App\Models\Task' => 'Aufgabe',
                                'App\Models\Invoice' => 'Rechnung',
                                'App\Models\Supplier' => 'Lieferant',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('documentable_id')
                            ->label('Zugeordnet zu')
                            ->options(function (callable $get) {
                                $type = $get('documentable_type');
                                if (!$type) return [];

                                return match ($type) {
                                    'App\Models\SolarPlant' => \App\Models\SolarPlant::whereNotNull('name')
                                        ->where('name', '!=', '')
                                        ->get()
                                        ->pluck('name', 'id')
                                        ->filter()
                                        ->toArray(),
                                    'App\Models\Customer' => \App\Models\Customer::whereNotNull('customer_number')
                                        ->whereNotNull('company_name')
                                        ->where('customer_number', '!=', '')
                                        ->where('company_name', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($customer) {
                                            return [$customer->id => $customer->customer_number . ' - ' . $customer->company_name];
                                        })
                                        ->toArray(),
                                    'App\Models\Task' => \App\Models\Task::whereNotNull('task_number')
                                        ->whereNotNull('title')
                                        ->where('task_number', '!=', '')
                                        ->where('title', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($task) {
                                            return [$task->id => $task->task_number . ' - ' . $task->title];
                                        })
                                        ->toArray(),
                                    'App\Models\Invoice' => \App\Models\Invoice::whereNotNull('invoice_number')
                                        ->where('invoice_number', '!=', '')
                                        ->pluck('invoice_number', 'id')
                                        ->filter()
                                        ->toArray(),
                                    'App\Models\Supplier' => \App\Models\Supplier::whereNotNull('supplier_number')
                                        ->whereNotNull('company_name')
                                        ->where('supplier_number', '!=', '')
                                        ->where('company_name', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($supplier) {
                                            return [$supplier->id => $supplier->supplier_number . ' - ' . $supplier->company_name];
                                        })
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (Document $record): string => $record->icon)
                    ->color('primary')
                    ->size('lg'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->description(fn (Document $record): ?string => $record->description ?
                        \Illuminate\Support\Str::limit($record->description, 80) : null
                    ),

                TextColumn::make('path')
                    ->label('Speicherort')
                    ->formatStateUsing(fn (string $state): string => dirname($state))
                    ->badge()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('documentType.name')
                    ->label('Dokumententyp')
                    ->badge()
                    ->color(fn (Document $record): string => $record->documentType?->color ?? 'gray')
                    ->icon(fn (Document $record): string => $record->documentType?->icon ?? 'heroicon-o-document')
                    ->sortable(),

                TextColumn::make('documentable_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\Models\SolarPlant' => 'Solaranlage',
                        'App\Models\Customer' => 'Kunde',
                        'App\Models\Task' => 'Aufgabe',
                        'App\Models\Invoice' => 'Rechnung',
                        'App\Models\Supplier' => 'Lieferant',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('documentable.name')
                    ->label('Zugeordnet zu')
                    ->getStateUsing(function (Document $record) {
                        return match ($record->documentable_type) {
                            'App\Models\SolarPlant' => $record->documentable?->name,
                            'App\Models\Customer' => $record->documentable?->company_name,
                            'App\Models\Task' => $record->documentable?->title,
                            'App\Models\Invoice' => $record->documentable?->invoice_number,
                            'App\Models\Supplier' => $record->documentable?->company_name,
                            default => 'Unbekannt',
                        };
                    })
                    ->searchable()
                    ->limit(30),

                TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->sortable(['size']),

                TextColumn::make('mime_type')
                    ->label('Dateityp')
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
                    ->sortable(),

                TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                SelectFilter::make('document_type_id')
                    ->label('Dokumententyp')
                    ->options(\App\Models\DocumentType::getSelectOptions())
                    ->searchable(),

                SelectFilter::make('documentable_type')
                    ->label('Zuordnungstyp')
                    ->options([
                        'App\Models\SolarPlant' => 'Solaranlage',
                        'App\Models\Customer' => 'Kunde',
                        'App\Models\Task' => 'Aufgabe',
                        'App\Models\Invoice' => 'Rechnung',
                        'App\Models\Supplier' => 'Lieferant',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (): bool => auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false),
                    Action::make('download')
                        ->label('Herunterladen')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Document $record): string => route('documents.download', $record))
                        ->openUrlInNewTab(),
                    Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Document $record): string => route('documents.preview', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => str_contains($record->mime_type, 'image') || str_contains($record->mime_type, 'pdf')),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (): bool => auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    // Zugriffskontrolle für Dokumente (Administrator, Superadmin und Manager)
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin', 'Manager'])->exists() ?? false;
    }

    /**
     * Konfigurierbare Verzeichnisstruktur basierend auf DocumentPathSetting
     */
    public static function getConfigurableDirectory(string $documentableType, $documentableId, ?string $category = null): string
    {
        // Lade das Model
        $model = $documentableType::find($documentableId);
        if (!$model) {
            return static::getFallbackDirectory($documentableType);
        }

        // Suche nach passender Pfadkonfiguration
        $pathSetting = DocumentPathSetting::getPathConfig($documentableType, $category);
        
        if (!$pathSetting) {
            // Fallback auf Standard-Pfadkonfiguration ohne Kategorie
            $pathSetting = DocumentPathSetting::getPathConfig($documentableType);
        }

        if (!$pathSetting) {
            // Letzter Fallback auf alte Logik
            return static::getFallbackDirectory($documentableType, $model);
        }

        // Generiere Pfad basierend auf Konfiguration
        return $pathSetting->generatePath($model);
    }

    /**
     * Fallback-Verzeichnis wenn keine Konfiguration gefunden wird
     */
    public static function getFallbackDirectory(string $documentableType, $model = null): string
    {
        $basePath = match ($documentableType) {
            'App\Models\SolarPlant' => 'solaranlagen',
            'App\Models\Customer' => 'kunden',
            'App\Models\Task' => 'aufgaben',
            'App\Models\Invoice' => 'rechnungen',
            'App\Models\Supplier' => 'lieferanten',
            default => 'allgemein',
        };

        if (!$model) {
            return $basePath . '/unbekannt';
        }

        // Versuche eine sinnvolle Nummer/ID zu finden
        $identifier = match ($documentableType) {
            'App\Models\SolarPlant' => $model->plant_number ?? $model->name ?? 'unbekannt',
            'App\Models\Customer' => $model->customer_number ?? 'unbekannt',
            'App\Models\Task' => $model->task_number ?? 'unbekannt',
            'App\Models\Invoice' => $model->invoice_number ?? 'unbekannt',
            'App\Models\Supplier' => $model->supplier_number ?? 'unbekannt',
            default => 'unbekannt',
        };

        // Bereinige den Identifier
        $cleanIdentifier = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $identifier);
        
        return $basePath . '/' . $cleanIdentifier;
    }

    /**
     * Generiert einen konfigurierbaren Dateinamen
     */
    public static function generateConfigurableFilename(string $originalFilename, string $documentableType, $documentableId, ?string $category = null): string
    {
        // Hole die Pfadkonfiguration
        $pathConfig = DocumentPathSetting::getPathConfig($documentableType, $category);
        
        if (!$pathConfig) {
            // Fallback auf Standard-Pfadkonfiguration ohne Kategorie
            $pathConfig = DocumentPathSetting::getPathConfig($documentableType);
        }

        if (!$pathConfig) {
            // Keine Konfiguration gefunden, verwende Original-Dateinamen
            return $originalFilename;
        }

        // Lade das zugehörige Model für Template-Platzhalter
        $model = null;
        if ($pathConfig->filename_strategy === 'template') {
            $model = $documentableType::find($documentableId);
        }

        // Generiere den Dateinamen
        return $pathConfig->generateFilename($originalFilename, $model);
    }
}
