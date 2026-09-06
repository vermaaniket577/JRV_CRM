<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Comprehensive health and readiness check for Load Balancers & Auto-Scalers.
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // 1. MySQL Database Ping
        try {
            DB::connection()->getPdo();
            $dbLatencyStart = microtime(true);
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $dbLatencyStart) * 1000, 2);
            $checks['database'] = [
                'status' => 'connected',
                'latency_ms' => $dbLatencyMs,
            ];
        } catch (\Throwable $e) {
            $status = 'degraded';
            $checks['database'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }

        // 2. Cache / Redis Ping
        try {
            $cacheKey = 'health_ping_' . microtime(true);
            Cache::put($cacheKey, 1, 5);
            $cacheHit = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            $checks['cache'] = [
                'status' => $cacheHit === 1 ? 'connected' : 'degraded',
                'driver' => config('cache.default', 'file'),
            ];
        } catch (\Throwable $e) {
            $status = 'degraded';
            $checks['cache'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }

        // 3. Storage / Disk Free Space Check
        $freeDiskBytes = disk_free_space(storage_path());
        $checks['storage'] = [
            'free_gb' => round($freeDiskBytes / (1024 * 1024 * 1024), 2),
            'status' => $freeDiskBytes > (500 * 1024 * 1024) ? 'ok' : 'low_space',
        ];

        // 4. Server Node Metrics
        $nodeInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_ip' => request()->server('SERVER_ADDR', '127.0.0.1'),
            'server_hostname' => gethostname() ?: 'crm-node-1',
            'timestamp' => now()->toIso8601String(),
        ];

        $httpCode = $status === 'healthy' ? 200 : 503;

        return response()->json([
            'status' => $status,
            'node' => $nodeInfo,
            'checks' => $checks,
        ], $httpCode);
    }
}
