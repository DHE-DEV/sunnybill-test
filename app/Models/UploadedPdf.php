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
                $fileSize = filesize($this->getFullPath());
                
                // Aktualisiere die Datenbank für zukünftige Aufrufe
                $this->updateQuietly(['file_size' => $fileSize]);
                
                \Log::info('Dateigröße dynamisch berechnet und aktualisiert', [
                    'uploaded_pdf_id' => $this->id,
                    'calculated_size' => $fileSize
                ]);
            } catch (\Exception $e) {
                \Log::warning('Konnte Dateigröße nicht dynamisch berechnen', [
                    'uploaded_pdf_id' => $this->id,
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
        return Storage::disk('pdf_uploads')->exists($this->file_path);
    }

    /**
     * Gibt den vollständigen Dateipfad zurück
     */
    public function getFullPath(): string
    {
        return Storage::disk('pdf_uploads')->path($this->file_path);
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