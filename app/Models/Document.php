<?php

namespace App\Models;

use App\Services\DocumentStorageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot-Methode für Model-Events
     */
    protected static function boot()
    {
        parent::boot();

        // Event-Listener für das Löschen von Dokumenten
        static::deleting(function ($document) {
            $document->deletePhysicalFile();
        });

        // Event-Listener für das endgültige Löschen (Force Delete)
        static::forceDeleting(function ($document) {
            $document->deletePhysicalFile();
        });
    }

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'category',
        'document_type_id',
        'description',
        'documentable_type',
        'documentable_id',
        'uploaded_by',
        'is_favorite',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_favorite' => 'boolean',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function getUrlAttribute(): string
    {
        return route('documents.preview', $this);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('documents.download', $this);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIconAttribute(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'pdf') => 'heroicon-o-document-text',
            str_contains($this->mime_type, 'image') => 'heroicon-o-photo',
            str_contains($this->mime_type, 'word') => 'heroicon-o-document',
            str_contains($this->mime_type, 'excel') => 'heroicon-o-table-cells',
            str_contains($this->mime_type, 'zip') => 'heroicon-o-archive-box',
            default => 'heroicon-o-document',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
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
            default => 'Sonstige',
        };
    }

    public static function getCategories(): array
    {
        return [
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
        ];
    }

    /**
     * Löscht die physische Datei vom Storage (DigitalOcean Spaces oder lokal)
     */
    public function deletePhysicalFile(): bool
    {
        if (!$this->path) {
            Log::info('Document::deletePhysicalFile: Kein Pfad vorhanden', [
                'document_id' => $this->id,
                'document_name' => $this->name
            ]);
            return true; // Kein Pfad = nichts zu löschen
        }

        try {
            // Bestimme die richtige Disk basierend auf dem Document
            $disk = $this->getDiskInstance();
            
            if ($disk->exists($this->path)) {
                $deleted = $disk->delete($this->path);
                
                Log::info('Document::deletePhysicalFile: Datei gelöscht', [
                    'document_id' => $this->id,
                    'document_name' => $this->name,
                    'path' => $this->path,
                    'disk' => $this->disk,
                    'success' => $deleted
                ]);
                
                return $deleted;
            } else {
                Log::warning('Document::deletePhysicalFile: Datei existiert nicht', [
                    'document_id' => $this->id,
                    'document_name' => $this->name,
                    'path' => $this->path,
                    'disk' => $this->disk
                ]);
                
                return true; // Datei existiert nicht = erfolgreich "gelöscht"
            }
        } catch (\Exception $e) {
            Log::error('Document::deletePhysicalFile: Fehler beim Löschen', [
                'document_id' => $this->id,
                'document_name' => $this->name,
                'path' => $this->path,
                'disk' => $this->disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Gibt die korrekte Disk-Instanz für dieses Dokument zurück
     */
    public function getDiskInstance()
    {
        // Wenn das Dokument eine spezifische Disk hat, verwende diese
        if ($this->disk && $this->disk !== 'documents') {
            return Storage::disk($this->disk);
        }
        
        // Für 'documents' Disk oder wenn keine Disk angegeben ist, verwende DocumentStorageService
        return DocumentStorageService::getDisk();
    }

    /**
     * Prüft ob die physische Datei existiert
     */
    public function fileExists(): bool
    {
        if (!$this->path) {
            return false;
        }

        try {
            $disk = $this->getDiskInstance();
            return $disk->exists($this->path);
        } catch (\Exception $e) {
            Log::error('Document::fileExists: Fehler beim Prüfen der Datei-Existenz', [
                'document_id' => $this->id,
                'path' => $this->path,
                'disk' => $this->disk,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Gibt die Dateigröße der physischen Datei zurück
     */
    public function getActualFileSize(): ?int
    {
        if (!$this->path) {
            return null;
        }

        try {
            $disk = $this->getDiskInstance();
            if ($disk->exists($this->path)) {
                return $disk->size($this->path);
            }
        } catch (\Exception $e) {
            Log::error('Document::getActualFileSize: Fehler beim Ermitteln der Dateigröße', [
                'document_id' => $this->id,
                'path' => $this->path,
                'disk' => $this->disk,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Synchronisiert die Metadaten mit der physischen Datei
     */
    public function syncMetadata(): bool
    {
        if (!$this->path) {
            return false;
        }

        try {
            $disk = $this->getDiskInstance();
            if (!$disk->exists($this->path)) {
                return false;
            }

            $actualSize = $disk->size($this->path);
            
            // Aktualisiere nur wenn sich die Größe geändert hat
            if ($this->size !== $actualSize) {
                $this->size = $actualSize;
                $this->save();
                
                Log::info('Document::syncMetadata: Metadaten synchronisiert', [
                    'document_id' => $this->id,
                    'old_size' => $this->getOriginal('size'),
                    'new_size' => $actualSize
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Document::syncMetadata: Fehler beim Synchronisieren der Metadaten', [
                'document_id' => $this->id,
                'path' => $this->path,
                'disk' => $this->disk,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
