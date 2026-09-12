<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoadBalancerNode;
use App\Services\LoadBalancerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class LoadBalancerController extends Controller
{
    protected LoadBalancerService $service;

    public function __construct(LoadBalancerService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the Load Balancer Dashboard.
     */
    public function index(Request $request): InertiaResponse
    {
        $nodes = $this->service->getNodes();
        $algorithm = $this->service->getActiveAlgorithm();

        $totalRequests = $nodes->sum('total_requests');
        $failedRequests = $nodes->sum('failed_requests');
        $activeConnections = $nodes->sum('active_connections');
        $avgLatency = $nodes->where('avg_latency_ms', '>', 0)->avg('avg_latency_ms') ?: 0;

        $stats = [
            'total_nodes' => $nodes->count(),
            'healthy_nodes' => $nodes->where('status', 'healthy')->where('is_enabled', true)->count(),
            'degraded_nodes' => $nodes->where('status', 'degraded')->count(),
            'offline_nodes' => $nodes->where('status', 'offline')->count(),
            'maintenance_nodes' => $nodes->where('status', 'maintenance')->count(),
            'total_requests' => $totalRequests,
            'failed_requests' => $failedRequests,
            'success_rate' => $totalRequests > 0 ? round((($totalRequests - $failedRequests) / $totalRequests) * 100, 1) : 100,
            'active_connections' => $activeConnections,
            'avg_latency_ms' => round($avgLatency, 2),
            'algorithm' => $algorithm,
        ];

        $algorithms = [
            [
                'id' => LoadBalancerService::ALGORITHM_ROUND_ROBIN,
                'name' => 'Round Robin',
                'description' => 'Sequentially rotates incoming requests evenly across all healthy cluster nodes in cyclic order.',
                'ideal_for' => 'Homogeneous server clusters with equal capacity.',
            ],
            [
                'id' => LoadBalancerService::ALGORITHM_WEIGHTED_ROUND_ROBIN,
                'name' => 'Weighted Round Robin',
                'description' => 'Distributes requests proportionally based on each server node’s assigned capacity weight.',
                'ideal_for' => 'Mixed clusters with different node specs (e.g., 8-core vs 2-core instances).',
            ],
            [
                'id' => LoadBalancerService::ALGORITHM_LEAST_CONN,
                'name' => 'Least Connections',
                'description' => 'Routes new requests dynamically to the server instance currently handling the lowest active connections.',
                'ideal_for' => 'Long-running database queries, file uploads, and intensive report generation.',
            ],
            [
                'id' => LoadBalancerService::ALGORITHM_IP_HASH,
                'name' => 'IP / Tenant Sticky Hash',
                'description' => 'Hashes the client IP or tenant subdomain to consistently route user sessions to the same node.',
                'ideal_for' => 'Sticky state, local websocket sessions, and local caching optimization.',
            ],
            [
                'id' => LoadBalancerService::ALGORITHM_LOWEST_LATENCY,
                'name' => 'Lowest Latency / Fastest Response',
                'description' => 'Directs traffic to the server node with the lowest measured health check response time.',
                'ideal_for' => 'Geographically distributed nodes or variable cloud latency.',
            ],
            [
                'id' => LoadBalancerService::ALGORITHM_RANDOM,
                'name' => 'Power of Two Choices (Random)',
                'description' => 'Picks two random healthy nodes and dispatches to the one with fewer active connections.',
                'ideal_for' => 'High-throughput microservices clusters to eliminate herd behavior.',
            ],
        ];

        $simulation = $this->service->simulate(100, $algorithm);

        return Inertia::render('Admin/LoadBalancer/Index', [
            'nodes' => $nodes,
            'stats' => $stats,
            'algorithm' => $algorithm,
            'algorithms' => $algorithms,
            'initialSimulation' => $simulation,
            'nginxConfig' => $this->service->generateNginxConfig(),
            'haproxyConfig' => $this->service->generateHaProxyConfig(),
        ]);
    }

    /**
     * Add a new upstream node.
     */
    public function storeNode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:150'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', 'string', 'in:http,https'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'health_check_url' => ['nullable', 'string', 'max:255'],
            'is_backup' => ['nullable', 'boolean'],
        ]);

        $node = LoadBalancerNode::create([
            'name' => $validated['name'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'protocol' => $validated['protocol'],
            'weight' => $validated['weight'],
            'health_check_url' => $validated['health_check_url'] ?: '/up',
            'is_backup' => $validated['is_backup'] ?? false,
            'status' => 'healthy',
            'is_enabled' => true,
        ]);

        // Probe immediately
        $this->service->checkNodeHealth($node);

        return redirect()->back()->with('success', "Cluster node '{$node->name}' successfully added!");
    }

    /**
     * Update an upstream node.
     */
    public function updateNode(Request $request, LoadBalancerNode $node): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:150'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', 'string', 'in:http,https'],
            'weight' => ['required', 'integer', 'min:1', 'max:100'],
            'health_check_url' => ['nullable', 'string', 'max:255'],
            'is_backup' => ['nullable', 'boolean'],
        ]);

        $node->update($validated);

        return redirect()->back()->with('success', "Node '{$node->name}' configuration updated.");
    }

    /**
     * Delete an upstream node.
     */
    public function destroyNode(LoadBalancerNode $node): RedirectResponse
    {
        $name = $node->name;
        $node->delete();

        return redirect()->back()->with('success', "Node '{$name}' removed from cluster pool.");
    }

    /**
     * Toggle node maintenance / drain mode.
     */
    public function toggleDrain(LoadBalancerNode $node): RedirectResponse
    {
        if ($node->status === 'maintenance') {
            $node->update(['status' => 'healthy']);
            $msg = "Node '{$node->name}' is now Active in the cluster.";
        } else {
            $node->update(['status' => 'maintenance']);
            $msg = "Node '{$node->name}' entered Drain / Maintenance Mode (no new traffic).";
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Toggle node enabled status.
     */
    public function toggleEnabled(LoadBalancerNode $node): RedirectResponse
    {
        $node->update(['is_enabled' => !$node->is_enabled]);
        $status = $node->is_enabled ? 'enabled' : 'disabled';

        return redirect()->back()->with('success', "Node '{$node->name}' is now {$status}.");
    }

    /**
     * Ping / check health of a single node.
     */
    public function pingNode(LoadBalancerNode $node): JsonResponse|RedirectResponse
    {
        $result = $this->service->checkNodeHealth($node);

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        $notice = $result['success']
            ? "Node '{$node->name}' is {$result['status']} ({$result['latency_ms']}ms)."
            : "Node '{$node->name}' health check failed: {$result['message']}";

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $notice);
    }

    /**
     * Ping all nodes in the cluster.
     */
    public function pingAll(): RedirectResponse
    {
        $results = $this->service->checkAllNodesHealth();
        $healthyCount = collect($results)->where('success', true)->count();
        $total = count($results);

        return redirect()->back()->with('success', "Cluster health probe completed: {$healthyCount} of {$total} nodes healthy.");
    }

    /**
     * Switch active load balancing algorithm.
     */
    public function updateAlgorithm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'algorithm' => ['required', 'string'],
        ]);

        $this->service->setAlgorithm($validated['algorithm']);

        return redirect()->back()->with('success', "Load balancing strategy changed to '{$validated['algorithm']}'.");
    }

    /**
     * Run simulated traffic distribution.
     */
    public function simulate(Request $request): JsonResponse
    {
        $count = (int)$request->input('requests', 100);
        $algo = $request->input('algorithm');

        $result = $this->service->simulate($count, $algo);

        return response()->json($result);
    }

    /**
     * Download generated NGINX or HAProxy config.
     */
    public function exportConfig(Request $request): Response
    {
        $type = $request->query('type', 'nginx');

        if ($type === 'haproxy') {
            $content = $this->service->generateHaProxyConfig();
            $filename = 'haproxy.cfg';
        } else {
            $content = $this->service->generateNginxConfig();
            $filename = 'load-balancer.conf';
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Software Layer 7 Reverse Proxy Gateway.
     * Routes incoming requests to target upstream node.
     */
    public function proxyGateway(Request $request, ?string $path = null): Response
    {
        return $this->service->forward($request, $path);
    }

    /**
     * Public Cluster Health & Status Probe Endpoint.
     */
    public function clusterStatus(): JsonResponse
    {
        $nodes = $this->service->getNodes();
        $healthyCount = $nodes->where('status', 'healthy')->where('is_enabled', true)->count();
        $total = $nodes->count();

        return response()->json([
            'load_balancer' => 'JRV CRM Software Load Balancer',
            'status' => $healthyCount > 0 ? 'operational' : 'critical',
            'active_algorithm' => $this->service->getActiveAlgorithm(),
            'healthy_nodes' => $healthyCount,
            'total_nodes' => $total,
            'nodes' => $nodes->map(fn($n) => [
                'name' => $n->name,
                'endpoint' => $n->base_url,
                'status' => $n->status,
                'latency_ms' => $n->avg_latency_ms,
                'weight' => $n->weight,
                'is_backup' => $n->is_backup,
            ]),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
