<?php

namespace App\Http\Controllers;

use App\Http\Resources\MemberResource;
use App\Models\Interaction;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\MemberPreference;
use App\Models\MemberShortlist;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MemberSearchFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->query('per_page', 25);
        $query = MemberSearchFilter::apply($request);
        $members = $query->paginate($perPage)->withQueryString();

        $counselors = User::where('is_super_admin', false)->get(['id', 'name', 'email']);

        $metrics = [
            'total_members' => Member::count(),
            'verified_members' => Member::where('verification_status', 'Verified')->count(),
            'pending_verification' => Member::where('verification_status', 'Pending Review')->count(),
            'paid_members' => Subscription::where('payment_status', 'Paid')->count(),
            'successful_matches' => Interaction::where('stage', 'Matched')->count(),
        ];

        return Inertia::render('Matrimonial/Index', [
            'members' => MemberResource::collection($members),
            'metrics' => $metrics,
            'counselors' => $counselors,
            'filters' => $request->only([
                'query', 'gender', 'age_min', 'age_max', 'height_min', 'height_max',
                'caste', 'sub_caste', 'gotra', 'education', 'occupation', 'state', 'city', 'verification_status', 'per_page'
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'date_of_birth' => ['required', 'date'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'marital_status' => ['required', 'string'],
            'religion' => ['required', 'string'],
            'caste' => ['required', 'string'],
            'sub_caste' => ['nullable', 'string'],
            'gotra' => ['nullable', 'string'],
            'mother_gotra' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string'],
            'occupation_type' => ['nullable', 'string'],
            'annual_income' => ['nullable', 'numeric'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:members,email'],
            'state' => ['required', 'string'],
            'city' => ['required', 'string'],
            'assigned_matchmaker_id' => ['nullable', 'integer', 'exists:users,id'],
            'plan_tier' => ['nullable', 'string', 'in:Free,Premium,Platinum,VIP'],
        ]);

        $dob = new \DateTime($validated['date_of_birth']);
        $age = $dob->diff(new \DateTime())->y;

        $member = Member::create(array_merge($validated, [
            'member_code' => 'JSM-' . strtoupper(substr($validated['gender'], 0, 1)) . '-' . mt_rand(10000, 99999),
            'age' => $age,
            'verification_status' => 'Pending Review',
            'status' => 'Active',
        ]));

        // Create default preference
        MemberPreference::create([
            'member_id' => $member->id,
            'age_min' => max(18, $age - 5),
            'age_max' => $age + 5,
            'caste' => $validated['caste'],
            'preferred_state' => $validated['state'],
        ]);

        // Create default subscription
        Subscription::create([
            'member_id' => $member->id,
            'plan_tier' => $validated['plan_tier'] ?? 'Premium',
            'amount_paid' => $validated['plan_tier'] === 'Platinum' ? 5100.00 : 2100.00,
            'currency' => 'INR',
            'payment_status' => 'Paid',
            'contact_view_limit' => 50,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        return redirect()->back()->with('success', "Member bio-data profile {$member->member_code} created successfully.");
    }

    public function shortlist(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'target_member_id' => ['required', 'integer', 'exists:members,id'],
            'notes' => ['nullable', 'string'],
        ]);

        MemberShortlist::updateOrCreate(
            [
                'client_member_id' => $member->id,
                'shortlisted_member_id' => $validated['target_member_id'],
            ],
            [
                'notes' => $validated['notes'] ?? null,
                'shortlisted_by' => auth()->id() ?? 1,
            ]
        );

        return redirect()->back()->with('success', 'Candidate shortlisted for client.');
    }

    public function addInteraction(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'target_member_id' => ['required', 'integer', 'exists:members,id'],
            'stage' => ['required', 'string', 'in:Interested,Contact Shared,Meeting Scheduled,Talks in Progress,Matched,Closed Lost'],
            'notes' => ['nullable', 'string'],
        ]);

        Interaction::create([
            'member_id' => $member->id,
            'target_member_id' => $validated['target_member_id'],
            'stage' => $validated['stage'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id() ?? 1,
        ]);

        if ($validated['stage'] === 'Matched') {
            $member->update(['status' => 'Match Found']);
            Member::where('id', $validated['target_member_id'])->update(['status' => 'Match Found']);
        }

        return redirect()->back()->with('success', 'Matchmaking stage updated.');
    }
}
