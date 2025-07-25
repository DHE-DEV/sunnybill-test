<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UploadedPdfResource\Pages;
use App\Models\UploadedPdf;
use App\Models\DocumentPathSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UploadedPdfResource extends Resource
{
    protected static ?string $model = UploadedPdf::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'PDF-Analyse';

    protected static ?string $pluralModelLabel = 'PDF-Analysen';

    protected static ?string $navigationGroup = 'PDF-Analyse System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('PDF-Informationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Beschreibender Name für die PDF'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->placeholder('Optionale Beschreibung der PDF-Datei')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('PDF-Datei')
                            ->disk('documents')
                            ->directory(function () {
                                try {
                                    // Versuche DocumentPathSetting für UploadedPdf zu verwenden
                                    $pathSetting = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')
                                        ->whereNull('category') // Standard-Kategorie
                                        ->first();
                                    
                                    if ($pathSetting) {
                                        // Erstelle temporäres UploadedPdf-Objekt für Platzhalter
                                        $tempUploadedPdf = new UploadedPdf([
                                            'uploaded_by' => Auth::id(),
                                            'created_at' => now(),
                                        ]);
                                        
                                        $resolvedPath = $pathSetting->generatePath($tempUploadedPdf);
                                        
                                        \Log::info('PDF-Upload: Verwende DocumentPathSetting', [
                                            'path_template' => $pathSetting->path_template,
                                            'resolved_path' => $resolvedPath,
                                            'setting_id' => $pathSetting->id,
                                            'success' => true
                                        ]);
                                        
                                        return $resolvedPath;
                                    }
                                } catch (\Exception $e) {
                                    \Log::warning('PDF-Upload: DocumentPathSetting fehlgeschlagen', [
                                        'error' => $e->getMessage(),
                                        'fallback' => 'Verwende hardcodierten Pfad'
                                    ]);
                                }
                                
                                // Fallback: Hardcodierter Pfad
                                $fallbackPath = date('Y/m');
                                \Log::info('PDF-Upload: Verwende Fallback-Pfad', [
                                    'fallback_path' => $fallbackPath,
                                    'reason' => 'Kein DocumentPathSetting gefunden oder Fehler aufgetreten'
                                ]);
                                
                                return $fallbackPath;
                            })
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                // Generiere UUID für eindeutige Dateinamen
                                $uuid = Str::uuid()->toString();
                                $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                                
                                // UUID-basierter Dateiname mit Original-Extension
                                $uuidFileName = "{$uuid}.{$extension}";
                                
                                \Log::info('PDF-Upload: UUID-Dateiname generiert', [
                                    'original_filename' => $file->getClientOriginalName(),
                                    'uuid_filename' => $uuidFileName,
                                    'uuid' => $uuid,
                                    'extension' => $extension
                                ]);
                                
                                return $uuidFileName;
                            })
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(51200) // 50MB
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Nur PDF-Dateien sind erlaubt. Maximale Größe: 50MB'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Analyse-Status')
                    ->schema([
                        Forms\Components\Select::make('analysis_status')
                            ->label('Analyse-Status')
                            ->options(UploadedPdf::getAnalysisStatuses())
                            ->default('pending')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Placeholder::make('analysis_completed_at')
                            ->label('Analyse abgeschlossen am')
                            ->content(fn (UploadedPdf $record): string => 
                                $record->analysis_completed_at 
                                    ? $record->analysis_completed_at->format('d.m.Y H:i:s')
                                    : 'Noch nicht abgeschlossen'
                            )
                            ->visible(fn ($livewire) => $livewire instanceof Pages\EditUploadedPdf),
                    ])
                    ->columns(2)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditUploadedPdf),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('file_icon')
                    ->label('')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->size('lg'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                TextColumn::make('original_filename')
                    ->label('Dateiname')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                BadgeColumn::make('analysis_status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => UploadedPdf::getAnalysisStatuses()[$state] ?? $state)
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->sortable(['file_size']),

                TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('analysis_completed_at')
                    ->label('Analysiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Noch nicht analysiert'),
            ])
            ->filters([
                SelectFilter::make('analysis_status')
                    ->label('Analyse-Status')
                    ->options(UploadedPdf::getAnalysisStatuses()),

                SelectFilter::make('uploaded_by')
                    ->label('Hochgeladen von')
                    ->relationship('uploadedBy', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Standard-Aktionen nur für Manager/Superadmin
                    Tables\Actions\ViewAction::make()
                        ->visible(fn (): bool => auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (): bool => auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false),
                    
                    // Benutzerdefinierte Aktionen für alle berechtigten Benutzer
                    Action::make('analyze')
                        ->label('Analysieren')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->url(fn (UploadedPdf $record): string => route('uploaded-pdfs.analyze', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (UploadedPdf $record): bool => $record->fileExists()),
                    Action::make('view_pdf')
                        ->label('PDF anzeigen')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn (UploadedPdf $record): string => route('uploaded-pdfs.view-pdf', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (UploadedPdf $record): bool => $record->fileExists()),
                    Action::make('download')
                        ->label('Herunterladen')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (UploadedPdf $record): string => route('uploaded-pdfs.download', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (UploadedPdf $record): bool => $record->fileExists()),
                    
                    // Delete-Aktion nur für Manager/Superadmin
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (): bool => auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false),
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
            'index' => Pages\ListUploadedPdfs::route('/'),
            'create' => Pages\CreateUploadedPdf::route('/create'),
            'view' => Pages\ViewUploadedPdf::route('/{record}'),
            'edit' => Pages\EditUploadedPdf::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Zugriffskontrolle: User, Manager und Superadmin-Team-Mitglieder können die Liste sehen
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['User', 'Manager', 'Superadmin'])->exists() ?? false;
    }

    /**
     * Nur Manager und Superadmin können neue PDFs erstellen
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false;
    }

    /**
     * Nur Manager und Superadmin können PDFs bearbeiten
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false;
    }

    /**
     * Nur Manager und Superadmin können PDFs löschen
     */
    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false;
    }

    /**
     * Nur Manager und Superadmin können Bulk-Löschungen durchführen
     */
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false;
    }

    /**
     * Alle berechtigten Benutzer können einzelne PDFs anzeigen
     */
    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    /**
     * Wird beim Erstellen eines neuen Records aufgerufen
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = Auth::id();
        $data['analysis_status'] = 'pending';
        
        return $data;
    }

    /**
     * Wird nach dem Erstellen eines Records aufgerufen
     */
    public static function afterCreate(UploadedPdf $record): void
    {
        // Setze Dateigröße und MIME-Type
        if ($record->file_path && Storage::disk('documents')->exists($record->file_path)) {
            $record->update([
                'file_size' => Storage::disk('documents')->size($record->file_path),
                'mime_type' => Storage::disk('documents')->mimeType($record->file_path) ?? 'application/pdf',
            ]);
            
            \Log::info('PDF-Upload: Datei-Metadaten gesetzt', [
                'record_id' => $record->id,
                'file_path' => $record->file_path,
                'file_size' => $record->file_size,
                'mime_type' => $record->mime_type,
                'uploaded_by' => $record->uploaded_by,
                'disk' => 'documents'
            ]);
        } else {
            \Log::warning('PDF-Upload: Datei nicht gefunden nach Upload', [
                'record_id' => $record->id,
                'file_path' => $record->file_path,
                'disk' => 'documents',
            ]);
        }
    }
}
