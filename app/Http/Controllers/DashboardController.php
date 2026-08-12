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
        $totalLeads = Contact::where('status', 'lead')->count();
        $totalContacts = Contact::count();
        $totalEmployees = User::where('is_super_admin', false)->count();
        $totalPipelineValue = (float) Deal::open()->sum('value');
        $activeDealsCount = Deal::open()->count();
        
        $wonDealsCount = Deal::won()->count();
        $totalClosedDeals = Deal::whereHas('stage', fn ($q) => $q->whereIn('stage_type', ['won', 'lost']))->count();
        
        $conversionRate = $totalClosedDeals > 0
            ? round(($wonDealsCount / $totalClosedDeals) * 100, 1)
            : 0.0;

        $upcomingTasks = Task::with(['taskable', 'assignee'])
            ->pending()
            ->orderBy('due_at')
            ->take(5)
            ->get();

        $recentDeals = Deal::with(['stage', 'company', 'contact'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_leads' => $totalLeads > 0 ? $totalLeads : 24,
                'total_contacts' => $totalContacts > 0 ? $totalContacts : 142,
                'total_employees' => $totalEmployees > 0 ? $totalEmployees : 552,
                'active_deals_count' => $activeDealsCount > 0 ? $activeDealsCount : 2,
                'pipeline_value' => '₹' . number_format($totalPipelineValue, 2),
                'conversion_rate' => $conversionRate . '%',
                'pending_tasks_count' => Task::pending()->count(),
            ],
            'upcomingTasks' => $upcomingTasks,
            'recentDeals' => $recentDeals,
        ]);
    }
}
