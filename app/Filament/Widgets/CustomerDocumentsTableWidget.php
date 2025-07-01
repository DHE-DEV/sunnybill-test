<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use App\Services\DocumentUploadConfig;

class CustomerDocumentsTableWidget extends BaseWidget
{
    public ?int $customerId = null;
    
    protected static ?string $heading = 'Dokumente';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = null;
    

    public function mount(array $data = []): void
    {
        $this->customerId = $data['customerId'] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->where('documentable_type', \App\Models\Customer::class)
                    ->when($this->customerId, fn (Builder $query) => $query->where('documentable_id', $this->customerId))
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                ImageColumn::make('preview')
                    ->label('Vorschau')
                    ->getStateUsing(function (Document $record): ?string {
                        $extension = strtolower(pathinfo($record->path, PATHINFO_EXTENSION));
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            return $record->preview_url;
                        }
                        return null;
                    })
                    ->defaultImageUrl(function (Document $record): ?string {
                        // Für nicht-Bild-Dateien zeigen wir kein Fallback-Bild
                        return null;
                    })
                    ->size(40)
                    ->square()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Dateiname')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold')
                    ->tooltip(fn (Document $record): string => $record->name)
                    ->toggleable(isToggledHiddenByDefault: false),

                BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->colors([
                        'primary' => 'contract',
                        'success' => 'invoice',
                        'warning' => 'certificate',
                        'info' => 'correspondence',
                        'secondary' => 'other',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'contract' => 'Vertrag',
                        'invoice' => 'Rechnung',
                        'certificate' => 'Zertifikat',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                        default => $state ?? 'Unbekannt',
                    })
                    ->placeholder('Keine Kategorie')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->getStateUsing(function (Document $record): string {
                        if ($record->size > 0) {
                            return $record->formatted_size;
                        }
                        
                        // Automatische Größenkorrektur
                        $fullPath = storage_path('app/documents/' . $record->path);
                        if (file_exists($fullPath)) {
                            $actualSize = filesize($fullPath);
                            $record->update(['size' => $actualSize]);
                            return $record->fresh()->formatted_size;
                        }
                        
                        return 'Unbekannt';
                    })
                    ->badge()
                    ->color('secondary')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Hochgeladen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(40)
                    ->placeholder('Keine Beschreibung')
                    ->tooltip(fn (Document $record): ?string => $record->description)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn (Document $record): string => $record->preview_url)
                        ->openUrlInNewTab()
                        ->visible(function (Document $record): bool {
                            $extension = strtolower(pathinfo($record->path, PATHINFO_EXTENSION));
                            return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
                        }),
                        
                    Tables\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Document $record): string => $record->download_url)
                        ->openUrlInNewTab(),
                        
                    Tables\Actions\Action::make('edit')
                        ->label('Bearbeiten')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->form([
                            TextInput::make('name')
                                ->label('Dateiname')
                                ->required()
                                ->maxLength(255),
                            Select::make('category')
                                ->label('Kategorie')
                                ->options([
                                    'contract' => 'Vertrag',
                                    'invoice' => 'Rechnung',
                                    'certificate' => 'Zertifikat',
                                    'correspondence' => 'Korrespondenz',
                                    'other' => 'Sonstiges',
                                ])
                                ->placeholder('Kategorie wählen'),
                            Textarea::make('description')
                                ->label('Beschreibung')
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->fillForm(fn (Document $record): array => [
                            'name' => $record->name,
                            'category' => $record->category,
                            'description' => $record->description,
                        ])
                        ->action(function (array $data, Document $record): void {
                            $record->update($data);
                        }),
                        
                    Tables\Actions\Action::make('delete')
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Dokument löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie dieses Dokument löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->action(function (Document $record): void {
                            // Datei aus Storage löschen
                            if (Storage::disk('documents')->exists($record->path)) {
                                Storage::disk('documents')->delete($record->path);
                            }
                            
                            // Datenbank-Eintrag löschen
                            $record->delete();
                        }),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload')
                    ->label('Dokument hochladen')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        FileUpload::make('file')
                            ->label('Datei')
                            ->required()
                            ->disk('documents')
                            ->directory('temp')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'])
                            ->maxSize(10240), // 10MB
                        TextInput::make('name')
                            ->label('Dateiname (optional)')
                            ->maxLength(255)
                            ->helperText('Leer lassen für automatischen Namen'),
                        Select::make('category')
                            ->label('Kategorie')
                            ->options([
                                'contract' => 'Vertrag',
                                'invoice' => 'Rechnung',
                                'certificate' => 'Zertifikat',
                                'correspondence' => 'Korrespondenz',
                                'other' => 'Sonstiges',
                            ])
                            ->placeholder('Kategorie wählen'),
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data): void {
                        $uploadConfig = new DocumentUploadConfig();
                        $customer = \App\Models\Customer::find($this->customerId);
                        
                        if (!$customer || !isset($data['file'])) {
                            return;
                        }
                        
                        // Temporäre Datei verarbeiten
                        $tempPath = $data['file'];
                        $originalName = basename($tempPath);
                        
                        // Finalen Pfad und Namen generieren
                        $finalPath = $uploadConfig->generatePath('customer', $customer);
                        $finalName = $data['name'] ?: $uploadConfig->generateFilename($originalName, $customer);
                        
                        // Datei an finalen Ort verschieben
                        $fullFinalPath = $finalPath . '/' . $finalName;
                        Storage::disk('documents')->move($tempPath, $fullFinalPath);
                        
                        // Dateigröße ermitteln
                        $fileSize = Storage::disk('documents')->size($fullFinalPath);
                        
                        // MIME-Type aus der temporären Datei ermitteln
                        $mimeType = Storage::disk('documents')->mimeType($fullFinalPath) ?? 'application/octet-stream';
                        
                        // Dokument in Datenbank speichern
                        Document::create([
                            'documentable_type' => \App\Models\Customer::class,
                            'documentable_id' => $this->customerId,
                            'name' => $finalName,
                            'original_name' => $originalName,
                            'path' => $fullFinalPath,
                            'disk' => 'documents',
                            'mime_type' => $mimeType,
                            'size' => $fileSize,
                            'category' => $data['category'] ?? null,
                            'description' => $data['description'] ?? null,
                            'uploaded_by' => auth()->id(),
                        ]);
                    }),
                    
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'contract' => 'Vertrag',
                        'invoice' => 'Rechnung',
                        'certificate' => 'Zertifikat',
                        'correspondence' => 'Korrespondenz',
                        'other' => 'Sonstiges',
                    ])
                    ->placeholder('Alle Kategorien'),
            ])
            ->emptyStateHeading('Keine Dokumente vorhanden')
            ->emptyStateDescription('Laden Sie das erste Dokument für diesen Kunden hoch.')
            ->emptyStateIcon('heroicon-o-document')
            ->heading('Dokumente (' . ($this->customerId ? Document::where('documentable_type', \App\Models\Customer::class)->where('documentable_id', $this->customerId)->count() : 0) . ')');
    }
}