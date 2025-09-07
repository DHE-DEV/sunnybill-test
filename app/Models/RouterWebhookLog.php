<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouterWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id',
        'webhook_token',
        'raw_data',
        'validation_errors',
        'response_data',
        'processing_time_ms',
        'client_ip',
        'user_agent',
        'status',
        'http_response_code',
        'operator',
        'signal_strength',
        'network_type',
        'connection_time',
        'data_usage_mb',
        'router_ip'
    ];

    protected $casts = [
        'raw_data' => 'array',
        'validation_errors' => 'array',
        'response_data' => 'array',
        'processing_time_ms' => 'integer',
        'signal_strength' => 'integer',
        'data_usage_mb' => 'decimal:2'
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByRouter($query, $routerId)
    {
        return $query->where('router_id', $routerId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
