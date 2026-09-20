<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $request->user()?->tenant_id);

        $contactBase = Contact::query();
        $userBase = User::where('is_super_admin', false);
        $dealBase = Deal::query();
        $taskBase = Task::query();

        if ($tenantId) {
            $contactBase->where('tenant_id', $tenantId);
            $userBase->where('tenant_id', $tenantId);
            $dealBase->where('tenant_id', $tenantId);
            $taskBase->where('tenant_id', $tenantId);
        } else {
            $contactBase->whereRaw('1 = 0');
            $userBase->whereRaw('1 = 0');
            $dealBase->whereRaw('1 = 0');
            $taskBase->whereRaw('1 = 0');
        }

        $totalLeads = (clone $contactBase)->where('status', 'lead')->count();
        $totalContacts = (clone $contactBase)->count();
        $totalEmployees = (clone $userBase)->count();
        $totalPipelineValue = (float) (clone $dealBase)->open()->sum('value');
        $activeDealsCount = (clone $dealBase)->open()->count();
        
        $wonDealsCount = (clone $dealBase)->won()->count();
        $totalClosedDeals = (clone $dealBase)->whereHas('stage', fn ($q) => $q->whereIn('stage_type', ['won', 'lost']))->count();
        
        $conversionRate = $totalClosedDeals > 0
            ? round(($wonDealsCount / $totalClosedDeals) * 100, 1)
            : 0.0;

        $upcomingTasks = (clone $taskBase)->with(['taskable', 'assignee'])
            ->pending()
            ->orderBy('due_at')
            ->take(5)
            ->get();

        $recentDeals = (clone $dealBase)->with(['stage', 'company', 'contact'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_leads' => $totalLeads,
                'total_contacts' => $totalContacts,
                'total_employees' => $totalEmployees,
                'active_deals_count' => $activeDealsCount,
                'pipeline_value' => '₹' . number_format($totalPipelineValue, 2),
                'conversion_rate' => $conversionRate . '%',
                'pending_tasks_count' => (clone $taskBase)->pending()->count(),
            ],
            'upcomingTasks' => $upcomingTasks,
            'recentDeals' => $recentDeals,
        ]);
    }
}
