<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'category',
        'description',
        'documentable_type',
        'documentable_id',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
}