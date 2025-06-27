<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LexofficeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'action',
        'entity_id',
        'lexoffice_id',
        'request_data',
        'response_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Status-Badge für Filament
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'success' => 'Erfolgreich',
            'error' => 'Fehler',
            default => $this->status
        };
    }

    /**
     * Status-Farbe für Filament
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'success',
            'error' => 'danger',
            default => 'gray'
        };
    }

    /**
     * Typ-Badge für Filament
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'customer' => 'Kunde',
            'article' => 'Artikel',
            'invoice' => 'Rechnung',
            default => $this->type
        };
    }

    /**
     * Action-Badge für Filament
     */
    public function getActionBadgeAttribute(): string
    {
        return match($this->action) {
            'import' => 'Import',
            'export' => 'Export',
            'sync' => 'Synchronisation',
            default => $this->action
        };
    }

    /**
     * Erfolgreiche Logs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Fehlerhafte Logs
     */
    public function scopeErrors($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Logs für bestimmten Typ
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Logs für bestimmte Aktion
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Neueste Logs zuerst
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
