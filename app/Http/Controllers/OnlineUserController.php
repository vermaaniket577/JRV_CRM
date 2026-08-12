<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class OnlineUserController extends Controller
{
    public function index(Request $request): Response
    {
        $enterFrom = $request->query('enter_from');
        $enterTo = $request->query('enter_to');
        $activityFrom = $request->query('activity_from');
        $activityTo = $request->query('activity_to');
        $specialCase = $request->query('special_case');
        $religiousVerification = $request->query('religious_verification');
        $sortMode = $request->query('sort_mode', 'Name');
        $sortOrder = $request->query('sort_order', 'Asc');

        // Fetch Online / Universal Users
        $query = User::query();

        if (session()->has('tenant_id')) {
            $query->where('tenant_id', session('tenant_id'));
        }

        if ($sortOrder === 'Desc') {
            $query->orderBy('name', 'desc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $users = $query->latest()->get()->map(function ($u) {
            $isPaid = false;
            $planName = 'Free Trial User';

            if ($u->email === 'admin@jrvcrm.com' || str_contains($u->email, 'acme') || str_contains($u->email, 'skyline') || $u->id === 2 || $u->id === 3) {
                $isPaid = true;
                $planName = 'Paid Subscriber';
            }

            $u->account_type = $isPaid ? 'paid' : 'free';
            $u->plan_name = $isPaid ? 'Paid Subscriber' : 'Free Trial User';
            return $u;
        });

        // Compute Metric Card Summary
        $metrics = [
            'row1' => [
                ['label' => 'Total Users', 'count' => $users->count(), 'bg' => 'bg-indigo-100 text-indigo-800'],
                ['label' => 'Suspended User', 'count' => $users->where('status', 'suspended')->count(), 'bg' => 'bg-slate-200 text-slate-700'],
                ['label' => 'Active Users', 'count' => $users->where('status', 'active')->count(), 'bg' => 'bg-emerald-100 text-emerald-800'],
                ['label' => 'VIP Service', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                ['label' => 'Mediator Service', 'count' => 0, 'bg' => 'bg-rose-100 text-rose-800'],
                ['label' => 'Whatsapp Service', 'count' => 0, 'bg' => 'bg-emerald-100 text-emerald-800'],
            ],
            'row2' => [
                ['label' => 'Jain', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                ['label' => 'Hindu', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                ['label' => 'Other', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                ['label' => 'Baniya (Maheshwari & Agrawal)', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                ['label' => 'Team Data', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                ['label' => 'Payment History', 'count' => 0, 'bg' => 'bg-teal-100 text-teal-800'],
            ],
            'row3' => [
                ['label' => 'Verified Biodata', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                ['label' => 'Referral Users', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                ['label' => 'Added Users', 'count' => 0, 'bg' => 'bg-purple-100 text-purple-800'],
                ['label' => 'Promotion Users', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                ['label' => 'Staff Users', 'count' => $users->where('is_tenant_admin', false)->count(), 'bg' => 'bg-sky-100 text-sky-800'],
                ['label' => 'Admin Users', 'count' => $users->where('is_tenant_admin', true)->count(), 'bg' => 'bg-indigo-100 text-indigo-800'],
            ],
        ];

        return Inertia::render('OnlineUsers', [
            'metrics' => $metrics,
            'users' => $users,
            'filters' => [
                'enter_from' => $enterFrom,
                'enter_to' => $enterTo,
                'activity_from' => $activityFrom,
                'activity_to' => $activityTo,
                'special_case' => $specialCase,
                'religious_verification' => $religiousVerification,
                'sort_mode' => $sortMode,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,pending,suspended'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_tenant_admin' => $validated['role'] === 'Admin',
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->back()->with('success', 'User created successfully!');
    }
}
