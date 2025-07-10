<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UploadedPdf extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'uploaded_by',
        'analysis_status',
        'analysis_data',
        'analysis_completed_at',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'analysis_completed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'analysis_completed_at',
    ];

    /**
     * Beziehung zum Benutzer, der die Datei hochgeladen hat
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Gibt die formatierte Dateigröße zurück
     */
    public function getFormattedSizeAttribute(): string
    {
        $fileSize = $this->file_size;
        
        // Fallback: Berechne Dateigröße dynamisch wenn sie in der DB fehlt
        if (!$fileSize && $this->fileExists()) {
            try {
                // Verwende documents-Disk für Dateigröße-Berechnung
                $fileSize = Storage::disk('documents')->size($this->file_path);
                
                // Aktualisiere die Datenbank für zukünftige Aufrufe
                $this->updateQuietly(['file_size' => $fileSize]);
                
                \Log::info('Dateigröße dynamisch von documents-Disk berechnet und aktualisiert', [
                    'uploaded_pdf_id' => $this->id,
                    'calculated_size' => $fileSize,
                    'file_path' => $this->file_path,
                    'disk' => 'documents'
                ]);
            } catch (\Exception $e) {
                \Log::warning('Konnte Dateigröße nicht von documents-Disk berechnen', [
                    'uploaded_pdf_id' => $this->id,
                    'file_path' => $this->file_path,
                    'error' => $e->getMessage()
                ]);
                return 'Unbekannt';
            }
        }
        
        if (!$fileSize) {
            return 'Unbekannt';
        }

        $bytes = $fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Prüft ob die Datei existiert
     */
    public function fileExists(): bool
    {
        \Log::info('UploadedPdf: fileExists() Check', [
            'uploaded_pdf_id' => $this->id,
            'file_path' => $this->file_path,
            'using_disk' => 'documents',
            'old_disk_was' => 's3'
        ]);
        
        return Storage::disk('documents')->exists($this->file_path);
    }

    /**
     * Gibt den vollständigen Dateipfad zurück
     * Funktioniert sowohl mit lokalen als auch S3-Disks über StorageSetting-Konfiguration
     */
    public function getFullPath(): string
    {
        \Log::info('UploadedPdf: getFullPath() verwendet documents-Disk', [
            'uploaded_pdf_id' => $this->id,
            'file_path' => $this->file_path,
            'using_disk' => 'documents'
        ]);
        
        return Storage::disk('documents')->path($this->file_path);
    }
    
    /**
     * Gibt die URL für die Datei zurück (funktioniert mit lokalen und S3-Disks)
     */
    public function getFileUrl(): string
    {
        \Log::info('UploadedPdf: getFileUrl() verwendet documents-Disk', [
            'uploaded_pdf_id' => $this->id,
            'file_path' => $this->file_path,
            'using_disk' => 'documents'
        ]);
        
        return Storage::disk('documents')->url($this->file_path);
    }
    
    /**
     * Gibt die S3-URL für die Datei zurück (Backward Compatibility)
     * @deprecated Verwende getFileUrl() stattdessen
     */
    public function getS3Url(): string
    {
        \Log::warning('UploadedPdf: getS3Url() ist deprecated', [
            'uploaded_pdf_id' => $this->id,
            'file_path' => $this->file_path,
            'message' => 'Verwende getFileUrl() stattdessen'
        ]);
        
        return $this->getFileUrl();
    }

    /**
     * Gibt die URL für den Download zurück
     */
    public function getDownloadUrl(): string
    {
        return route('uploaded-pdfs.download', $this);
    }

    /**
     * Gibt die URL für die Analyse zurück
     */
    public function getAnalysisUrl(): string
    {
        return route('uploaded-pdfs.analyze', $this);
    }

    /**
     * Verfügbare Analyse-Status
     */
    public static function getAnalysisStatuses(): array
    {
        return [
            'pending' => 'Ausstehend',
            'processing' => 'In Bearbeitung',
            'completed' => 'Abgeschlossen',
            'failed' => 'Fehlgeschlagen',
        ];
    }

    /**
     * Setzt den Analyse-Status
     */
    public function setAnalysisStatus(string $status, ?array $data = null): void
    {
        $this->update([
            'analysis_status' => $status,
            'analysis_data' => $data,
            'analysis_completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    /**
     * Scope für ausstehende Analysen
     */
    public function scopePendingAnalysis($query)
    {
        return $query->where('analysis_status', 'pending');
    }

    /**
     * Scope für abgeschlossene Analysen
     */
    public function scopeAnalyzed($query)
    {
        return $query->where('analysis_status', 'completed');
    }
}