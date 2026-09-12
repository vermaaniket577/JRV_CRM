<?php

namespace App\Services;

use App\Models\LoadBalancerNode;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoadBalancerService
{
    public const ALGORITHM_ROUND_ROBIN = 'round_robin';
    public const ALGORITHM_WEIGHTED_ROUND_ROBIN = 'weighted_round_robin';
    public const ALGORITHM_LEAST_CONN = 'least_conn';
    public const ALGORITHM_IP_HASH = 'ip_hash';
    public const ALGORITHM_LOWEST_LATENCY = 'lowest_latency';
    public const ALGORITHM_RANDOM = 'random';

    /**
     * Get the active load balancing algorithm from system settings or default.
     */
    public function getActiveAlgorithm(): string
    {
        $setting = SystemSetting::where('key', 'lb_algorithm')->first();
        return $setting ? $setting->value : self::ALGORITHM_ROUND_ROBIN;
    }

    /**
     * Set the active load balancing algorithm.
     */
    public function setAlgorithm(string $algorithm): void
    {
        $allowed = [
            self::ALGORITHM_ROUND_ROBIN,
            self::ALGORITHM_WEIGHTED_ROUND_ROBIN,
            self::ALGORITHM_LEAST_CONN,
            self::ALGORITHM_IP_HASH,
            self::ALGORITHM_LOWEST_LATENCY,
            self::ALGORITHM_RANDOM,
        ];

        if (in_array($algorithm, $allowed)) {
            SystemSetting::updateOrCreate(
                ['key' => 'lb_algorithm'],
                ['value' => $algorithm, 'group' => 'load_balancer']
            );
        }
    }

    /**
     * Get list of all upstream nodes, seeding default cluster nodes if table is empty.
     */
    public function getNodes()
    {
        if (LoadBalancerNode::count() === 0) {
            $this->seedDefaultNodes();
        }

        return LoadBalancerNode::orderBy('is_backup')->orderBy('id')->get();
    }

    /**
     * Seed realistic initial cluster nodes.
     */
    public function seedDefaultNodes(): void
    {
        $defaultNodes = [
            [
                'name' => 'Primary App Node (Node-1)',
                'host' => '127.0.0.1',
                'port' => 8000,
                'protocol' => 'http',
                'weight' => 3,
                'status' => 'healthy',
                'health_check_url' => '/up',
                'active_connections' => 4,
                'total_requests' => 1420,
                'failed_requests' => 2,
                'avg_latency_ms' => 8.45,
                'is_backup' => false,
                'is_enabled' => true,
            ],
            [
                'name' => 'Scale Worker Node (Node-2)',
                'host' => '127.0.0.1',
                'port' => 8080,
                'protocol' => 'http',
                'weight' => 2,
                'status' => 'healthy',
                'health_check_url' => '/api/health',
                'active_connections' => 2,
                'total_requests' => 980,
                'failed_requests' => 1,
                'avg_latency_ms' => 12.10,
                'is_backup' => false,
                'is_enabled' => true,
            ],
            [
                'name' => 'Background & Journal Pool (Node-3)',
                'host' => '127.0.0.1',
                'port' => 8001,
                'protocol' => 'http',
                'weight' => 1,
                'status' => 'healthy',
                'health_check_url' => '/up',
                'active_connections' => 1,
                'total_requests' => 560,
                'failed_requests' => 0,
                'avg_latency_ms' => 15.30,
                'is_backup' => false,
                'is_enabled' => true,
            ],
            [
                'name' => 'Failover Standby Node (Backup)',
                'host' => '127.0.0.1',
                'port' => 8002,
                'protocol' => 'http',
                'weight' => 1,
                'status' => 'healthy',
                'health_check_url' => '/up',
                'active_connections' => 0,
                'total_requests' => 12,
                'failed_requests' => 0,
                'avg_latency_ms' => 22.00,
                'is_backup' => true,
                'is_enabled' => true,
            ],
        ];

        foreach ($defaultNodes as $nodeData) {
            LoadBalancerNode::create($nodeData);
        }
    }

    /**
     * Get available upstream nodes (enabled and not offline/in maintenance).
     */
    public function getAvailableNodes()
    {
        $allAvailable = LoadBalancerNode::available()->where('is_backup', false)->get();

        // Prioritize healthy nodes over degraded ones
        $healthy = $allAvailable->where('status', 'healthy');
        if ($healthy->isNotEmpty()) {
            return $healthy->values();
        }

        if ($allAvailable->isNotEmpty()) {
            return $allAvailable->values();
        }

        // If no primary nodes available, use backup nodes
        $backups = LoadBalancerNode::available()->where('is_backup', true)->get();
        $healthyBackups = $backups->where('status', 'healthy');
        return ($healthyBackups->isNotEmpty() ? $healthyBackups : $backups)->values();
    }

    /**
     * Select optimal target node based on current balancing algorithm.
     */
    public function selectNode(Request $request, ?string $algorithm = null): ?LoadBalancerNode
    {
        $availableNodes = $this->getAvailableNodes();
        return $this->selectFromNodePool($availableNodes, $request, $algorithm);
    }

    /**
     * Select from a specific subset of available nodes.
     */
    public function selectFromNodePool($nodes, Request $request, ?string $algorithm = null): ?LoadBalancerNode
    {
        if ($nodes->isEmpty()) {
            return null;
        }

        if ($nodes->count() === 1) {
            return $nodes->first();
        }

        $algo = $algorithm ?: $this->getActiveAlgorithm();

        return match ($algo) {
            self::ALGORITHM_WEIGHTED_ROUND_ROBIN => $this->selectWeightedRoundRobin($nodes),
            self::ALGORITHM_LEAST_CONN => $this->selectLeastConnections($nodes),
            self::ALGORITHM_IP_HASH => $this->selectIpHash($nodes, $request),
            self::ALGORITHM_LOWEST_LATENCY => $this->selectLowestLatency($nodes),
            self::ALGORITHM_RANDOM => $this->selectRandomPowerOfTwo($nodes),
            default => $this->selectRoundRobin($nodes),
        };
    }

    /**
     * 1. Standard Round Robin Algorithm
     */
    protected function selectRoundRobin($nodes): LoadBalancerNode
    {
        $cacheKey = 'lb_rr_index';
        $index = (int)Cache::get($cacheKey, 0);

        $selected = $nodes[$index % $nodes->count()];
        Cache::put($cacheKey, ($index + 1) % $nodes->count(), 3600);

        return $selected;
    }

    /**
     * 2. Weighted Round Robin Algorithm
     */
    protected function selectWeightedRoundRobin($nodes): LoadBalancerNode
    {
        $pool = [];
        foreach ($nodes as $node) {
            $weight = max(1, (int)$node->weight);
            for ($i = 0; $i < $weight; $i++) {
                $pool[] = $node;
            }
        }

        $cacheKey = 'lb_wrr_index';
        $index = (int)Cache::get($cacheKey, 0);

        $selected = $pool[$index % count($pool)];
        Cache::put($cacheKey, ($index + 1) % count($pool), 3600);

        return $selected;
    }

    /**
     * 3. Least Connections Algorithm
     */
    protected function selectLeastConnections($nodes): LoadBalancerNode
    {
        return $nodes->sortBy('active_connections')->first();
    }

    /**
     * 4. Client IP / Subdomain Hash (Sticky Session) Algorithm
     */
    protected function selectIpHash($nodes, Request $request): LoadBalancerNode
    {
        // Hash either tenant host or client IP
        $clientIdentifier = $request->header('X-Forwarded-Host')
            ?? $request->getHost()
            ?? $request->ip()
            ?? '127.0.0.1';

        $hash = crc32($clientIdentifier);
        $index = abs($hash) % $nodes->count();

        return $nodes->values()->get($index);
    }

    /**
     * 5. Lowest Latency / Fastest Response Algorithm
     */
    protected function selectLowestLatency($nodes): LoadBalancerNode
    {
        return $nodes->sortBy(function ($n) {
            return $n->avg_latency_ms > 0 ? $n->avg_latency_ms : 9999;
        })->first();
    }

    /**
     * 6. Random with Power of Two Choices
     */
    protected function selectRandomPowerOfTwo($nodes): LoadBalancerNode
    {
        if ($nodes->count() <= 2) {
            return $nodes->random();
        }

        $randomPair = $nodes->random(2);
        return $randomPair->sortBy('active_connections')->first();
    }

    /**
     * Probe health of a specific node.
     */
    public function checkNodeHealth(LoadBalancerNode $node): array
    {
        $start = microtime(true);
        $url = $node->full_health_check_url;

        try {
            // First try HTTP probe
            $response = Http::timeout(3)->get($url);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                $node->recordSuccess($latency);
                return [
                    'success' => true,
                    'status' => 'healthy',
                    'latency_ms' => $latency,
                    'message' => "Node responded with HTTP {$response->status()} in {$latency}ms",
                ];
            }

            // Non-200 response
            $node->recordFailure("HTTP {$response->status()} returned from health endpoint");
            return [
                'success' => false,
                'status' => 'degraded',
                'latency_ms' => $latency,
                'message' => "HTTP {$response->status()}",
            ];
        } catch (\Throwable $e) {
            // Fallback: Check if socket connects
            $socketStart = microtime(true);
            $socket = @fsockopen($node->host, $node->port, $errno, $errstr, 2);
            $socketLatency = round((microtime(true) - $socketStart) * 1000, 2);

            if ($socket) {
                fclose($socket);
                $node->recordSuccess($socketLatency);
                return [
                    'success' => true,
                    'status' => 'healthy',
                    'latency_ms' => $socketLatency,
                    'message' => "TCP Port {$node->port} reachable in {$socketLatency}ms",
                ];
            }

            // Offline
            $node->recordFailure("Unreachable: {$e->getMessage()}");
            return [
                'success' => false,
                'status' => 'offline',
                'latency_ms' => 0,
                'message' => "Connection refused on port {$node->port}",
            ];
        }
    }

    /**
     * Probe health of all nodes in the cluster.
     */
    public function checkAllNodesHealth(): array
    {
        $results = [];
        $nodes = LoadBalancerNode::where('status', '!=', 'maintenance')->get();

        foreach ($nodes as $node) {
            $results[$node->id] = $this->checkNodeHealth($node);
        }

        return $results;
    }

    /**
     * Execute Layer 7 Reverse Proxy request to the selected upstream node.
     */
    public function forward(Request $request, ?string $targetPath = null): Response
    {
        $availableNodes = $this->getAvailableNodes();

        if ($availableNodes->isEmpty()) {
            return response()->json([
                'error' => 'No healthy upstream nodes available in the cluster.',
                'status' => 503,
                'cluster' => 'JRV CRM Load Balancer',
            ], 503);
        }

        $maxAttempts = min(3, $availableNodes->count());
        $attemptedNodes = [];
        $lastError = 'Unknown error';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $remainingNodes = $availableNodes->whereNotIn('id', $attemptedNodes)->values();
            if ($remainingNodes->isEmpty()) {
                break;
            }

            $node = $this->selectFromNodePool($remainingNodes, $request);
            if (!$node) {
                break;
            }

            $attemptedNodes[] = $node->id;
            $node->increment('active_connections');
            $startTime = microtime(true);

            try {
                $path = $targetPath ? ('/' . ltrim($targetPath, '/')) : $request->getRequestUri();
                $targetUrl = $node->base_url . $path;

                $headers = [
                    'X-Forwarded-For' => $request->ip(),
                    'X-Forwarded-Proto' => $request->getScheme(),
                    'X-Forwarded-Host' => $request->getHost(),
                    'X-Real-IP' => $request->ip(),
                    'User-Agent' => $request->userAgent() ?: 'JRV-LoadBalancer/1.0',
                ];

                if ($auth = $request->header('Authorization')) {
                    $headers['Authorization'] = $auth;
                }
                if ($ctype = $request->header('Content-Type')) {
                    $headers['Content-Type'] = $ctype;
                }

                $method = strtolower($request->getMethod());
                $body = $request->getContent();

                $client = Http::withHeaders($headers)->timeout(8);

                $upstreamResponse = match ($method) {
                    'post' => $client->withBody($body, $request->header('Content-Type', 'application/json'))->post($targetUrl),
                    'put' => $client->withBody($body, $request->header('Content-Type', 'application/json'))->put($targetUrl),
                    'patch' => $client->withBody($body, $request->header('Content-Type', 'application/json'))->patch($targetUrl),
                    'delete' => $client->delete($targetUrl),
                    default => $client->get($targetUrl),
                };

                $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
                $node->recordSuccess($latencyMs);

                return response($upstreamResponse->body(), $upstreamResponse->status())
                    ->header('X-Load-Balancer', 'JRV-Cluster-Engine')
                    ->header('X-Upstream-Node', $node->name)
                    ->header('X-Upstream-Latency', "{$latencyMs}ms")
                    ->header('Content-Type', $upstreamResponse->header('Content-Type', 'application/json'));

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $node->recordFailure($lastError);
                // Failover: loop will proceed to select next available node
            } finally {
                if ($node->active_connections > 0) {
                    $node->decrement('active_connections');
                }
            }
        }

        return response()->json([
            'error' => 'All attempted cluster nodes failed to respond.',
            'last_error' => $lastError,
            'attempted_nodes' => count($attemptedNodes),
            'cluster' => 'JRV CRM Load Balancer',
        ], 502);
    }

    /**
     * Simulate traffic distribution across nodes with given request count and algorithm.
     */
    public function simulate(int $requestCount = 100, ?string $algorithm = null): array
    {
        $nodes = $this->getAvailableNodes();

        if ($nodes->isEmpty()) {
            return [
                'total_requests' => $requestCount,
                'algorithm' => $algorithm ?: $this->getActiveAlgorithm(),
                'distribution' => [],
            ];
        }

        $algo = $algorithm ?: $this->getActiveAlgorithm();
        $counts = [];

        foreach ($nodes as $n) {
            $counts[$n->id] = 0;
        }

        // Temporary simulation clones to mimic connection state changes
        $simNodes = $nodes->map(function ($n) {
            return clone $n;
        });

        $fakeRequest = Request::create('http://localhost/', 'GET');

        for ($i = 0; $i < $requestCount; $i++) {
            // For IP hash simulation, rotate simulated IP addresses
            if ($algo === self::ALGORITHM_IP_HASH) {
                $fakeIp = '192.168.1.' . (($i % 25) + 1);
                $fakeRequest->server->set('REMOTE_ADDR', $fakeIp);
                $fakeRequest->headers->set('X-Forwarded-Host', "tenant-{$i}.localhost");
            }

            $chosen = $this->selectNode($fakeRequest, $algo);
            if ($chosen) {
                $counts[$chosen->id]++;
                // If simulating least connections, briefly simulate active connections
                if ($algo === self::ALGORITHM_LEAST_CONN) {
                    $chosen->active_connections++;
                }
            }
        }

        $distribution = [];
        foreach ($nodes as $n) {
            $cnt = $counts[$n->id] ?? 0;
            $percentage = $requestCount > 0 ? round(($cnt / $requestCount) * 100, 1) : 0;
            $distribution[] = [
                'id' => $n->id,
                'name' => $n->name,
                'host' => $n->host,
                'port' => $n->port,
                'weight' => $n->weight,
                'status' => $n->status,
                'allocated_requests' => $cnt,
                'percentage' => $percentage,
            ];
        }

        return [
            'total_requests' => $requestCount,
            'algorithm' => $algo,
            'distribution' => $distribution,
        ];
    }

    /**
     * Generate production NGINX Load Balancer configuration based on current active nodes.
     */
    public function generateNginxConfig(): string
    {
        $nodes = LoadBalancerNode::where('is_enabled', true)->get();
        $algo = $this->getActiveAlgorithm();

        $algoDirective = match ($algo) {
            self::ALGORITHM_LEAST_CONN => "    least_conn;\n",
            self::ALGORITHM_IP_HASH => "    ip_hash;\n",
            default => "",
        };

        $serverLines = "";
        foreach ($nodes as $n) {
            $backup = $n->is_backup ? " backup" : "";
            $weight = $n->weight > 1 ? " weight={$n->weight}" : "";
            $drain = $n->status === 'maintenance' ? " down" : "";
            $serverLines .= "    server {$n->host}:{$n->port}{$weight} max_fails=3 fail_timeout=10s{$backup}{$drain};\n";
        }

        return <<<NGINX
# ==============================================================================
# JRV CRM - Dynamic Production NGINX Load Balancer Configuration
# Generated by: JRV CRM Software Load Balancer
# Date: {date('Y-m-d H:i:s')}
# Active Strategy: {$algo}
# ==============================================================================

upstream jrv_crm_cluster {
{$algoDirective}{$serverLines}
    keepalive 32;
}

server {
    listen 80;
    listen [::]:80;
    server_name localhost *.localhost *.jrvcrm.com;

    client_max_body_size 100M;
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;

    # Health check endpoint
    location /up {
        proxy_pass http://jrv_crm_cluster/up;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        access_log off;
    }

    # Main application proxy
    location / {
        proxy_pass http://jrv_crm_cluster;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        proxy_connect_timeout 5s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
NGINX;
    }

    /**
     * Generate HAProxy Configuration.
     */
    public function generateHaProxyConfig(): string
    {
        $nodes = LoadBalancerNode::where('is_enabled', true)->get();
        $algo = $this->getActiveAlgorithm();

        $haproxyBalance = match ($algo) {
            self::ALGORITHM_LEAST_CONN => "balance leastconn",
            self::ALGORITHM_IP_HASH => "balance source",
            self::ALGORITHM_RANDOM => "balance random",
            default => "balance roundrobin",
        };

        $serverLines = "";
        foreach ($nodes as $n) {
            $backup = $n->is_backup ? " backup" : "";
            $weight = " weight " . max(1, $n->weight);
            $disabled = $n->status === 'maintenance' ? " disabled" : "";
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $n->name);
            $serverLines .= "    server {$safeName} {$n->host}:{$n->port} check inter 5s rise 2 fall 3{$weight}{$backup}{$disabled}\n";
        }

        return <<<HAPROXY
# ==============================================================================
# JRV CRM - Production HAProxy Load Balancer Configuration
# Generated by: JRV CRM Software Load Balancer
# Date: {date('Y-m-d H:i:s')}
# ==============================================================================

global
    log /dev/log local0
    maxconn 4096

defaults
    log global
    mode http
    option httplog
    option dontlognull
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms

frontend jrv_crm_frontend
    bind *:80
    mode http
    option forwardfor
    default_backend jrv_crm_backend

backend jrv_crm_backend
    mode http
    {$haproxyBalance}
    cookie SERVERID insert indirect nocache
{$serverLines}
HAPROXY;
    }
}
