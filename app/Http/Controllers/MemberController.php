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
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? \App\Models\Tenant::with('industry')->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug ?? \App\Models\TenantSetting::getByKey('industry_slug', 'education', $tenantId);

        if ($industrySlug === 'real-estate') {
            return redirect('/properties');
        }
        if ($industrySlug !== 'matrimonial') {
            return redirect('/tenant/crm-records');
        }

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

    public function verified(Request $request): Response
    {
        $request->merge(['verification_status' => 'Verified']);
        return $this->index($request);
    }

    public function shortlistIndex(Request $request): Response
    {
        $request->merge(['is_shortlisted' => true]);
        return $this->index($request);
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

    public function export(Request $request)
    {
        $members = Member::with(['preferences', 'latestSubscription'])->latest()->get();
        $filename = 'biodata_members_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Member Code', 'First Name', 'Last Name', 'Gender', 'Date of Birth', 'Age',
                'Marital Status', 'Religion', 'Caste', 'Sub Caste', 'Gotra', 'Phone', 'Email',
                'City', 'State', 'Country', 'Verification Status', 'Status'
            ]);

            foreach ($members as $m) {
                fputcsv($file, [
                    $m->member_code,
                    $m->first_name,
                    $m->last_name,
                    $m->gender,
                    $m->date_of_birth,
                    $m->age,
                    $m->marital_status,
                    $m->religion,
                    $m->caste,
                    $m->sub_caste ?? '',
                    $m->gotra ?? '',
                    $m->phone,
                    $m->email,
                    $m->city,
                    $m->state,
                    $m->country ?? 'India',
                    $m->verification_status,
                    $m->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"sample_biodata_template.csv\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'First Name', 'Last Name', 'Gender', 'Date of Birth', 'Marital Status',
                'Religion', 'Caste', 'Phone', 'Email', 'City', 'State'
            ]);
            fputcsv($file, [
                'Aarav', 'Jain', 'Male', '1995-04-12', 'Never Married',
                'Jain', 'Digambar', '+91 9876543210', 'aarav.jain@example.com', 'Mumbai', 'Maharashtra'
            ]);
            fputcsv($file, [
                'Ananya', 'Agarwal', 'Female', '1997-08-25', 'Never Married',
                'Hindu', 'Agarwal', '+91 9876543211', 'ananya.agarwal@example.com', 'New Delhi', 'Delhi'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $importedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0]) || empty($row[7]) || empty($row[8])) continue;

            $firstName = trim($row[0]);
            $lastName = isset($row[1]) && !empty(trim($row[1])) ? trim($row[1]) : 'Candidate';
            $gender = isset($row[2]) && in_array(trim($row[2]), ['Male', 'Female', 'Other']) ? trim($row[2]) : 'Male';
            $dob = isset($row[3]) && !empty(trim($row[3])) ? trim($row[3]) : '1996-01-01';
            $marital = isset($row[4]) && !empty(trim($row[4])) ? trim($row[4]) : 'Never Married';
            $religion = isset($row[5]) && !empty(trim($row[5])) ? trim($row[5]) : 'Hindu';
            $caste = isset($row[6]) && !empty(trim($row[6])) ? trim($row[6]) : 'General';
            $phone = trim($row[7]);
            $email = trim($row[8]);
            $city = isset($row[9]) && !empty(trim($row[9])) ? trim($row[9]) : 'Mumbai';
            $state = isset($row[10]) && !empty(trim($row[10])) ? trim($row[10]) : 'Maharashtra';

            $age = 28;
            try {
                $dobDate = new \DateTime($dob);
                $age = $dobDate->diff(new \DateTime())->y;
            } catch (\Exception $e) {
                $dob = '1996-01-01';
            }

            $member = Member::updateOrCreate(
                ['email' => $email],
                [
                    'member_code' => 'JSM-' . strtoupper(substr($gender, 0, 1)) . '-' . mt_rand(10000, 99999),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $gender,
                    'date_of_birth' => $dob,
                    'age' => $age,
                    'marital_status' => $marital,
                    'religion' => $religion,
                    'caste' => $caste,
                    'phone' => $phone,
                    'city' => $city,
                    'state' => $state,
                    'country' => 'India',
                    'verification_status' => 'Pending Review',
                    'status' => 'Active',
                ]
            );

            MemberPreference::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'age_min' => max(18, $age - 5),
                    'age_max' => $age + 5,
                    'caste' => $caste,
                    'preferred_state' => $state,
                ]
            );

            Subscription::firstOrCreate(
                ['member_id' => $member->id],
                [
                    'plan_tier' => 'Free',
                    'amount_paid' => 0.00,
                    'currency' => 'INR',
                    'payment_status' => 'Paid',
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                ]
            );

            $importedCount++;
        }

        fclose($handle);

        return redirect()->back()->with('success', "{$importedCount} biodata profiles successfully imported.");
    }
}
