<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $currentUserId = auth()->id() ?? User::first()?->id ?? 1;
        $activeTab = $request->query('type', 'received'); // 'received' or 'send'
        $selectedStatus = $request->query('status', 'all');
        $priorityFilter = $request->query('priority');
        $staffFilter = $request->query('staff_id');
        $searchTitle = $request->query('title');

        // Query base tasks
        $baseQuery = Task::with(['assignee', 'creator']);

        if ($activeTab === 'received') {
            $baseQuery->where('assigned_to', $currentUserId);
        } else {
            $baseQuery->where('created_by', $currentUserId);
        }

        // Calculate Tab Counts
        $receivedCount = Task::where('assigned_to', $currentUserId)->count();
        $sentCount = Task::where('created_by', $currentUserId)->count();

        // Calculate Status Metric Counts
        $statusCounts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'hold' => (clone $baseQuery)->where('status', 'hold')->count(),
            'done' => (clone $baseQuery)->where('status', 'completed')->count(),
            'rework' => (clone $baseQuery)->where('status', 'rework')->count(),
            'verified' => (clone $baseQuery)->where('status', 'verified')->count(),
        ];

        // Apply Status Filter
        if ($selectedStatus !== 'all') {
            $dbStatus = match ($selectedStatus) {
                'done' => 'completed',
                default => $selectedStatus,
            };
            $baseQuery->where('status', $dbStatus);
        }

        // Apply Priority Filter
        if ($priorityFilter) {
            $baseQuery->where('priority', strtolower($priorityFilter));
        }

        // Apply Staff Filter
        if ($staffFilter) {
            $baseQuery->where('assigned_to', $staffFilter);
        }

        // Apply Search Title Filter
        if ($searchTitle) {
            $baseQuery->where('title', 'like', "%{$searchTitle}%");
        }

        $tasks = $baseQuery->latest()->get();

        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? auth()->user()?->tenant_id);

        $staffQuery = User::where('is_super_admin', false);
        if ($tenantId) {
            $staffQuery->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $staffQuery->whereRaw('1 = 0');
        }
        $staffList = $staffQuery->get(['id', 'name', 'email']);

        return Inertia::render('TaskDashboard', [
            'activeTab' => $activeTab,
            'selectedStatus' => $selectedStatus,
            'receivedCount' => $receivedCount,
            'sentCount' => $sentCount,
            'statusCounts' => $statusCounts,
            'tasks' => $tasks,
            'staffList' => $staffList,
            'filters' => [
                'priority' => $priorityFilter,
                'staff_id' => $staffFilter,
                'title' => $searchTitle,
            ],
        ]);
    }
}
