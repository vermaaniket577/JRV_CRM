<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoadBalancerNode extends Model
{
    use HasFactory;

    protected $table = 'load_balancer_nodes';

    protected $fillable = [
        'name',
        'host',
        'port',
        'protocol',
        'weight',
        'status',
        'health_check_url',
        'active_connections',
        'total_requests',
        'failed_requests',
        'avg_latency_ms',
        'is_backup',
        'is_enabled',
        'last_checked_at',
        'last_error',
    ];

    protected $casts = [
        'port' => 'integer',
        'weight' => 'integer',
        'active_connections' => 'integer',
        'total_requests' => 'integer',
        'failed_requests' => 'integer',
        'avg_latency_ms' => 'float',
        'is_backup' => 'boolean',
        'is_enabled' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    protected $appends = [
        'base_url',
        'full_health_check_url',
        'success_rate',
    ];

    public function getBaseUrlAttribute(): string
    {
        $portStr = (($this->protocol === 'http' && $this->port === 80) || ($this->protocol === 'https' && $this->port === 443))
            ? ''
            : ":{$this->port}";

        return "{$this->protocol}://{$this->host}{$portStr}";
    }

    public function getFullHealthCheckUrlAttribute(): string
    {
        $path = '/' . ltrim($this->health_check_url ?: '/up', '/');
        return $this->base_url . $path;
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 100.0;
        }

        $successful = max(0, $this->total_requests - $this->failed_requests);
        return round(($successful / $this->total_requests) * 100, 1);
    }

    public function isAvailable(): bool
    {
        return $this->is_enabled && !in_array($this->status, ['offline', 'maintenance']);
    }

    public function recordSuccess(float $latencyMs): void
    {
        $this->increment('total_requests');

        // Exponential moving average for latency
        $currentAvg = (float)$this->avg_latency_ms;
        $newAvg = $currentAvg > 0 ? ($currentAvg * 0.8 + $latencyMs * 0.2) : $latencyMs;

        $this->update([
            'avg_latency_ms' => round($newAvg, 2),
            'status' => 'healthy',
            'last_error' => null,
            'last_checked_at' => now(),
        ]);
    }

    public function recordFailure(string $errorMessage): void
    {
        $this->increment('total_requests');
        $this->increment('failed_requests');

        $this->update([
            'status' => 'degraded',
            'last_error' => substr($errorMessage, 0, 500),
            'last_checked_at' => now(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_enabled', true)
                     ->whereNotIn('status', ['offline', 'maintenance']);
    }
}
