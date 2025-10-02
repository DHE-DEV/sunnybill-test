<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'article_id',
        'version_number',
        'name',
        'description',
        'type',
        'price',
        'tax_rate',
        'unit',
        'decimal_places',
        'total_decimal_places',
        'changed_by',
        'change_reason',
        'changed_fields',
        'is_current',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'tax_rate' => 'decimal:4',
        'decimal_places' => 'integer',
        'total_decimal_places' => 'integer',
        'changed_fields' => 'array',
        'is_current' => 'boolean',
    ];

    /**
     * Beziehung zum Artikel
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Formatierter Preis mit dynamischen Dezimalstellen
     */
    public function getFormattedPriceDynamicAttribute(): string
    {
        return rtrim(rtrim(number_format($this->price, 6, ',', '.'), '0'), ',');
    }

    /**
     * Formatierter Steuersatz als Prozent
     */
    public function getFormattedTaxRateAttribute(): string
    {
        return number_format($this->tax_rate * 100, 2) . '%';
    }

    /**
     * Scope für aktuelle Version
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope für bestimmten Artikel
     */
    public function scopeForArticle($query, $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Erstelle eine neue Version für einen Artikel
     */
    public static function createVersion(Article $article, array $changedFields = [], string $changeReason = null, string $changedBy = null): self
    {
        // Aktuelle Version deaktivieren
        static::where('article_id', $article->id)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        // Nächste Versionsnummer ermitteln
        $nextVersion = static::where('article_id', $article->id)
            ->max('version_number') + 1;

        // Neue Version erstellen
        return static::create([
            'article_id' => $article->id,
            'version_number' => $nextVersion,
            'name' => $article->name,
            'description' => $article->description,
            'type' => $article->type ?? 'SERVICE',
            'price' => $article->price,
            'tax_rate' => $article->getCurrentTaxRate(), // Use getCurrentTaxRate() to support both tax_rate and tax_rate_id
            'unit' => $article->unit ?? 'Stück',
            'decimal_places' => $article->decimal_places ?? 2,
            'total_decimal_places' => $article->total_decimal_places ?? 2,
            'changed_by' => $changedBy ?? auth()->user()?->name ?? 'System',
            'change_reason' => $changeReason,
            'changed_fields' => $changedFields,
            'is_current' => true,
        ]);
    }

    /**
     * Hole eine bestimmte Version eines Artikels
     */
    public static function getVersion(Article $article, int $versionNumber): ?self
    {
        return static::where('article_id', $article->id)
            ->where('version_number', $versionNumber)
            ->first();
    }

    /**
     * Hole die aktuelle Version eines Artikels
     */
    public static function getCurrentVersion(Article $article): ?self
    {
        return static::where('article_id', $article->id)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Hole alle Versionen eines Artikels
     */
    public static function getVersionHistory(Article $article)
    {
        return static::where('article_id', $article->id)
            ->orderBy('version_number', 'desc')
            ->get();
    }
}
