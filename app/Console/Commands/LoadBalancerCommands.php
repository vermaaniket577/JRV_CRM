<?php

namespace App\Console\Commands;

use App\Models\LoadBalancerNode;
use App\Services\LoadBalancerService;
use Illuminate\Console\Command;

class LoadBalancerStatusCommand extends Command
{
    protected $signature = 'lb:status';
    protected $description = 'Display JRV CRM Software Load Balancer cluster status, upstream nodes, and traffic metrics';

    public function handle(LoadBalancerService $service): int
    {
        $this->info('====================================================');
        $this->info('⚡ JRV CRM Enterprise Software Load Balancer');
        $this->info('====================================================');

        $algo = $service->getActiveAlgorithm();
        $this->line("<comment>Active Load Balancing Strategy:</comment> <info>{$algo}</info>");

        $nodes = $service->getNodes();
        if ($nodes->isEmpty()) {
            $this->warn('No upstream nodes registered in the cluster.');
            return 0;
        }

        $headers = ['ID', 'Node Name', 'Target Endpoint', 'Weight', 'Status', 'Active Conns', 'Total Reqs', 'Success %', 'Avg Latency'];
        $rows = [];

        foreach ($nodes as $node) {
            $statusColor = match ($node->status) {
                'healthy' => "<info>{$node->status}</info>",
                'degraded' => "<comment>{$node->status}</comment>",
                'maintenance' => "<fg=magenta>{$node->status}</>",
                default => "<fg=red>{$node->status}</>",
            };

            $rows[] = [
                $node->id,
                $node->name . ($node->is_backup ? ' [Backup]' : ''),
                $node->base_url,
                $node->weight,
                $statusColor,
                $node->active_connections,
                number_format($node->total_requests),
                $node->success_rate . '%',
                $node->avg_latency_ms . ' ms',
            ];
        }

        $this->table($headers, $rows);

        $healthyCount = $nodes->where('status', 'healthy')->where('is_enabled', true)->count();
        $this->line("Cluster Summary: <info>{$healthyCount}</info> / {$nodes->count()} healthy nodes operating.");

        return 0;
    }
}

class LoadBalancerCheckCommand extends Command
{
    protected $signature = 'lb:check';
    protected $description = 'Probe health and measure latency for all cluster nodes';

    public function handle(LoadBalancerService $service): int
    {
        $this->info('Probing all cluster nodes...');
        $nodes = $service->getNodes();

        foreach ($nodes as $node) {
            $this->output->write("Checking {$node->name} ({$node->base_url})... ");
            $res = $service->checkNodeHealth($node);

            if ($res['success']) {
                $this->line("<info>[OK] {$res['status']} ({$res['latency_ms']}ms)</info>");
            } else {
                $this->line("<fg=red>[FAIL] {$res['status']} - {$res['message']}</>");
            }
        }

        $this->info('Health check complete.');
        return 0;
    }
}

class LoadBalancerSimulateCommand extends Command
{
    protected $signature = 'lb:simulate {requests=100 : Number of requests to simulate} {--algorithm= : Balancing algorithm to test}';
    protected $description = 'Simulate load balancer traffic distribution across healthy nodes';

    public function handle(LoadBalancerService $service): int
    {
        $requests = (int)$this->argument('requests');
        $algo = $this->option('algorithm') ?: $service->getActiveAlgorithm();

        $this->info("Simulating {$requests} requests using '{$algo}' strategy...\n");
        $res = $service->simulate($requests, $algo);

        $headers = ['Node ID', 'Node Name', 'Host:Port', 'Weight', 'Status', 'Allocated Requests', 'Traffic Share %'];
        $rows = [];

        foreach ($res['distribution'] as $d) {
            $rows[] = [
                $d['id'],
                $d['name'],
                $d['host'] . ':' . $d['port'],
                $d['weight'],
                $d['status'],
                $d['allocated_requests'],
                $d['percentage'] . '%',
            ];
        }

        $this->table($headers, $rows);
        return 0;
    }
}
