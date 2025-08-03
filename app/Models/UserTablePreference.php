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
        'infolist_state',
    ];

    protected $casts = [
        'filters' => 'array',
        'search' => 'array',
        'sort' => 'array',
        'column_searches' => 'array',
        'infolist_state' => 'array',
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
                'infolist_state' => $data['infolist_state'] ?? null,
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
            'infolist_state' => $preference->infolist_state,
        ], fn($value) => !is_null($value));
    }

    /**
     * Speichere speziell den Infolist-Status
     */
    public static function saveInfolistState(int $userId, string $tableName, array $infolistState): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'table_name' => $tableName,
            ],
            [
                'infolist_state' => $infolistState,
            ]
        );
    }

    /**
     * Lade speziell den Infolist-Status
     */
    public static function getInfolistState(int $userId, string $tableName): ?array
    {
        $preference = static::where('user_id', $userId)
            ->where('table_name', $tableName)
            ->first();

        return $preference?->infolist_state;
    }
}
