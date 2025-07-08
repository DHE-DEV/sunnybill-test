<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gmail_id',
        'subject',
        'from_email',
        'total_labels',
        'all_labels',
        'system_labels',
        'category_labels',
        'user_labels',
        'has_inbox',
        'is_unread',
        'is_important',
        'is_starred',
        'filter_active',
        'action',
        'notes',
    ];

    protected $casts = [
        'all_labels' => 'array',
        'system_labels' => 'array',
        'category_labels' => 'array',
        'user_labels' => 'array',
        'has_inbox' => 'boolean',
        'is_unread' => 'boolean',
        'is_important' => 'boolean',
        'is_starred' => 'boolean',
        'filter_active' => 'boolean',
    ];

    /**
     * Scope für E-Mails mit INBOX Label
     */
    public function scopeWithInbox($query)
    {
        return $query->where('has_inbox', true);
    }

    /**
     * Scope für E-Mails trotz aktivem Filter
     */
    public function scopeInboxDespiteFilter($query)
    {
        return $query->where('has_inbox', true)->where('filter_active', true);
    }

    /**
     * Scope für bestimmte Aktion
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope für heute
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Accessor für formatierte Labels
     */
    public function getFormattedLabelsAttribute()
    {
        return implode(', ', $this->all_labels ?? []);
    }

    /**
     * Accessor für Label-Anzahl nach Typ
     */
    public function getLabelCountsAttribute()
    {
        return [
            'system' => count($this->system_labels ?? []),
            'category' => count($this->category_labels ?? []),
            'user' => count($this->user_labels ?? []),
            'total' => $this->total_labels,
        ];
    }

    /**
     * Statische Methode zum Erstellen eines Log-Eintrags
     */
    public static function createFromEmailData($emailData, $action = 'sync', $notes = null)
    {
        return self::create([
            'gmail_id' => $emailData['gmail_id'],
            'subject' => $emailData['subject'] ?? null,
            'from_email' => $emailData['from'] ?? null,
            'total_labels' => $emailData['total_labels'] ?? 0,
            'all_labels' => $emailData['all_labels'] ?? [],
            'system_labels' => $emailData['system_labels'] ?? [],
            'category_labels' => $emailData['category_labels'] ?? [],
            'user_labels' => $emailData['user_labels'] ?? [],
            'has_inbox' => $emailData['has_inbox'] ?? false,
            'is_unread' => $emailData['is_unread'] ?? false,
            'is_important' => $emailData['is_important'] ?? false,
            'is_starred' => $emailData['is_starred'] ?? false,
            'filter_active' => $emailData['filter_active'] ?? false,
            'action' => $action,
            'notes' => $notes,
        ]);
    }
}
