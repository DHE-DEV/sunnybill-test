<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SimCard extends Model
{
    protected $fillable = [
        'iccid',
        'msisdn',
        'imsi',
        'pin_code',
        'puk_code',
        'provider',
        'tariff',
        'contract_type',
        'monthly_cost',
        'contract_start',
        'contract_end',
        'apn',
        'data_limit_mb',
        'data_used_mb',
        'is_active',
        'is_blocked',
        'status',
        'last_activity',
        'signal_strength',
        'router_id',
        'assigned_to',
        'location',
        'description',
        'additional_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'last_activity' => 'datetime',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'monthly_cost' => 'decimal:2',
        'data_limit_mb' => 'integer',
        'data_used_mb' => 'integer',
        'signal_strength' => 'integer',
        'additional_data' => 'json',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_blocked' => false,
        'status' => 'active',
        'data_used_mb' => 0,
        'contract_type' => 'postpaid',
    ];

    /**
     * Relationship to Router
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Provider options for select fields
     */
    public static function getProviderOptions(): array
    {
        return [
            'Telekom' => 'Deutsche Telekom',
            'Vodafone' => 'Vodafone',
            'O2' => 'Telefónica O2',
            '1&1' => '1&1',
            'Congstar' => 'Congstar',
            'Freenet' => 'Freenet',
            'IoT Provider' => 'IoT Provider',
            'Sonstiges' => 'Sonstiges',
        ];
    }

    /**
     * Contract type options for select fields
     */
    public static function getContractTypeOptions(): array
    {
        return [
            'prepaid' => 'Prepaid',
            'postpaid' => 'Vertrag',
            'iot' => 'IoT/M2M',
        ];
    }

    /**
     * Status options for select fields
     */
    public static function getStatusOptions(): array
    {
        return [
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'suspended' => 'Gesperrt',
            'expired' => 'Abgelaufen',
        ];
    }

    /**
     * Check if contract is expiring soon (within 30 days)
     */
    public function isContractExpiringSoon(): bool
    {
        if (!$this->contract_end) {
            return false;
        }

        return $this->contract_end->isBetween(now(), now()->addDays(30));
    }

    /**
     * Check if contract has expired
     */
    public function isContractExpired(): bool
    {
        if (!$this->contract_end) {
            return false;
        }

        return $this->contract_end->isPast();
    }

    /**
     * Calculate data usage percentage
     */
    public function getDataUsagePercentage(): ?float
    {
        if (!$this->data_limit_mb || $this->data_limit_mb === 0) {
            return null;
        }

        return min(100, ($this->data_used_mb / $this->data_limit_mb) * 100);
    }

    /**
     * Check if data limit is nearly exceeded (> 80%)
     */
    public function isDataLimitNearlyExceeded(): bool
    {
        $percentage = $this->getDataUsagePercentage();
        return $percentage && $percentage > 80;
    }

    /**
     * Check if data limit is exceeded
     */
    public function isDataLimitExceeded(): bool
    {
        $percentage = $this->getDataUsagePercentage();
        return $percentage && $percentage >= 100;
    }

    /**
     * Get formatted data usage
     */
    public function getFormattedDataUsage(): string
    {
        if (!$this->data_limit_mb) {
            return $this->data_used_mb . ' MB';
        }

        $percentage = $this->getDataUsagePercentage();
        return $this->data_used_mb . ' / ' . $this->data_limit_mb . ' MB (' . number_format($percentage, 1) . '%)';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_blocked) {
            return 'danger';
        }

        return match($this->status) {
            'active' => 'success',
            'inactive' => 'warning',
            'suspended' => 'danger',
            'expired' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->is_blocked) {
            return 'Gesperrt';
        }

        return match($this->status) {
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'suspended' => 'Suspendiert',
            'expired' => 'Abgelaufen',
            default => 'Unbekannt',
        };
    }

    /**
     * Get data usage color for badge
     */
    public function getDataUsageColorAttribute(): string
    {
        $percentage = $this->getDataUsagePercentage();
        
        if (!$percentage) {
            return 'gray';
        }

        if ($percentage >= 100) {
            return 'danger';
        } elseif ($percentage >= 80) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Get contract status color
     */
    public function getContractStatusColorAttribute(): string
    {
        if ($this->isContractExpired()) {
            return 'danger';
        } elseif ($this->isContractExpiringSoon()) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Get formatted contract end date
     */
    public function getFormattedContractEndAttribute(): ?string
    {
        if (!$this->contract_end) {
            return null;
        }

        $diffInDays = $this->contract_end->diffInDays(now());
        $status = '';

        if ($this->contract_end->isPast()) {
            $status = ' (abgelaufen)';
        } elseif ($diffInDays <= 30) {
            $status = " (läuft in {$diffInDays} Tagen ab)";
        }

        return $this->contract_end->format('d.m.Y') . $status;
    }

    /**
     * Get last activity formatted
     */
    public function getLastActivityFormattedAttribute(): string
    {
        if (!$this->last_activity) {
            return 'Nie';
        }

        $diff = $this->last_activity->diffInMinutes(now());
        
        if ($diff < 1) {
            return 'Gerade eben';
        } elseif ($diff < 60) {
            return "vor {$diff} Minute" . ($diff > 1 ? 'n' : '');
        } elseif ($diff < 1440) {
            $hours = floor($diff / 60);
            return "vor {$hours} Stunde" . ($hours > 1 ? 'n' : '');
        } else {
            return $this->last_activity->format('d.m.Y H:i') . ' Uhr';
        }
    }

    /**
     * Reset monthly data usage (to be called monthly)
     */
    public function resetMonthlyDataUsage(): void
    {
        $this->update(['data_used_mb' => 0]);
    }

    /**
     * Update data usage
     */
    public function updateDataUsage(int $usageMB): void
    {
        $this->increment('data_used_mb', $usageMB);
        $this->update(['last_activity' => now()]);
    }

    /**
     * Get display name for the SIM card
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->assigned_to) {
            return $this->assigned_to . ' (' . $this->msisdn . ')';
        }

        if ($this->msisdn) {
            return $this->msisdn;
        }

        return substr($this->iccid, -8);
    }

    /**
     * Get label for the SIM card (primary identifier)
     */
    public function getLabelAttribute(): string
    {
        // Use MSISDN if available, otherwise ICCID suffix
        if ($this->msisdn) {
            return $this->msisdn;
        }

        return 'SIM-' . substr($this->iccid, -8);
    }

    /**
     * Get formatted signal strength
     */
    public function getFormattedSignalStrengthAttribute(): ?string
    {
        if (!$this->signal_strength) {
            return null;
        }

        return $this->signal_strength . ' dBm';
    }

    /**
     * Get signal strength color for badge
     */
    public function getSignalStrengthColorAttribute(): string
    {
        if (!$this->signal_strength) {
            return 'gray';
        }

        // Signal strength ranges (dBm):
        // -50 to -60: Excellent (green)
        // -60 to -70: Good (blue) 
        // -70 to -80: Fair (yellow)
        // -80 to -90: Poor (orange)
        // -90 and lower: Very poor (red)
        if ($this->signal_strength >= -60) {
            return 'success';
        } elseif ($this->signal_strength >= -70) {
            return 'info';
        } elseif ($this->signal_strength >= -80) {
            return 'warning';
        } elseif ($this->signal_strength >= -90) {
            return 'orange';
        } else {
            return 'danger';
        }
    }

    /**
     * Get signal strength quality text
     */
    public function getSignalStrengthQualityAttribute(): string
    {
        if (!$this->signal_strength) {
            return 'Unbekannt';
        }

        if ($this->signal_strength >= -60) {
            return 'Ausgezeichnet';
        } elseif ($this->signal_strength >= -70) {
            return 'Gut';
        } elseif ($this->signal_strength >= -80) {
            return 'Mäßig';
        } elseif ($this->signal_strength >= -90) {
            return 'Schwach';
        } else {
            return 'Sehr schwach';
        }
    }
}
