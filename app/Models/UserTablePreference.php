<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTablePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_name',
        'filters',
        'search',
        'sort',
        'column_searches',
    ];

    protected $casts = [
        'filters' => 'array',
        'search' => 'array',
        'sort' => 'array',
        'column_searches' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Speichere oder aktualisiere Benutzerpräferenzen für eine Tabelle
     */
    public static function savePreferences(int $userId, string $tableName, array $data): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'table_name' => $tableName,
            ],
            array_filter([
                'filters' => $data['filters'] ?? null,
                'search' => $data['search'] ?? null,
                'sort' => $data['sort'] ?? null,
                'column_searches' => $data['column_searches'] ?? null,
            ], fn($value) => !is_null($value))
        );
    }

    /**
     * Lade Benutzerpräferenzen für eine Tabelle
     */
    public static function getPreferences(int $userId, string $tableName): ?array
    {
        $preference = static::where('user_id', $userId)
            ->where('table_name', $tableName)
            ->first();

        if (!$preference) {
            return null;
        }

        return array_filter([
            'filters' => $preference->filters,
            'search' => $preference->search,
            'sort' => $preference->sort,
            'column_searches' => $preference->column_searches,
        ], fn($value) => !is_null($value));
    }
}
