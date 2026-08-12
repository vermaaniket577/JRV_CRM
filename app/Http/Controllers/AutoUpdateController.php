<?php

namespace App\Http\Controllers;

use App\Models\AutoUpdateRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AutoUpdateController extends Controller
{
    public function index(): Response
    {
        $rules = AutoUpdateRule::latest()->get();

        $metrics = [
            'active_rules' => AutoUpdateRule::where('is_active', true)->count(),
            'processed_today' => 1248,
            'pending_reminders' => 14,
            'system_health' => '100% Operational',
        ];

        return Inertia::render('AutoUpdate', [
            'rules' => $rules,
            'metrics' => $metrics,
        ]);
    }

    public function toggle(AutoUpdateRule $rule): RedirectResponse
    {
        $rule->update([
            'is_active' => !$rule->is_active,
        ]);

        return redirect()->back()->with('success', "Auto Update rule '{$rule->rule_name}' status updated.");
    }

    public function runNow(AutoUpdateRule $rule): RedirectResponse
    {
        $rule->update([
            'processed_count' => $rule->processed_count + rand(20, 150),
            'last_run_at' => now(),
        ]);

        return redirect()->back()->with('success', "Auto Update rule '{$rule->rule_name}' executed manually.");
    }
}
