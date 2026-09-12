<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\PaymentPlan;
use App\Models\Tenant;
use App\Models\TenantSetting;
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

        $isSubdomain = app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain');
        $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $tenantId = ($isSubdomain && $currentTenant) ? $currentTenant->id : (session('tenant_id') ?? $request->user()?->tenant_id);
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug 
            ?? TenantSetting::getByKey('industry_slug', 'education', $tenantId);

        // Fetch Online / Universal Users
        $query = User::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($isSubdomain) {
            $query->whereRaw('1 = 0');
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

        // Compute Live Counts
        $totalUsers = $users->count();
        $suspendedUsers = $users->where('status', 'suspended')->count();
        $activeUsers = $users->where('status', 'active')->count();
        $staffCount = $users->where('is_tenant_admin', false)->count();
        $adminCount = $users->where('is_tenant_admin', true)->count();
        
        $totalContacts = Contact::where('tenant_id', $tenantId)->count();
        $totalDeals = Deal::where('tenant_id', $tenantId)->count();
        $totalPlans = PaymentPlan::where('tenant_id', $tenantId)->count();

        // Sector-Wise Dynamic Metrics Configuration
        $metrics = match ($industrySlug) {
            'education' => [
                'sector_title' => 'Student & Admission Directory',
                'sector_desc' => 'Manage students, counselors, staff, and admission inquiries',
                'row1' => [
                    ['label' => 'Total Students / Leads', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Suspended Accounts', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active Admissions', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'VIP / Fast-track', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Counselor Assigned', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'WhatsApp Alerts', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'B.Tech / Engineering', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'MBA / Management', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Medical & MBBS', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Data Science & AI', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Batch 2026 Intake', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Fee Payment History', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Verified Applications', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Referral Leads', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Enrolled Students', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Scholarship Leads', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Counselors / Staff', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Admin Accounts', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            'research-publication', 'journal-publication', 'publication' => [
                'sector_title' => 'Author & Manuscript Submissions Directory',
                'sector_desc' => 'Manage authors, researchers, peer reviewers, and journal submissions',
                'row1' => [
                    ['label' => 'Total Manuscripts', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Rejected / Withdrawn', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Under Peer Review', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Fast-Track Track', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Reviewers Assigned', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'Author Submissions', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Scopus Indexed Journals', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'UGC CARE Journals', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Engineering & AI Track', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Medical & Pharma Track', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Plagiarism Clear (<10%)', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'APC Fee Invoices', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Verified Authors', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Revision Pending', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Published with DOI', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Conference Track', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Editorial Board', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Chief Editors', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            'real-estate' => [
                'sector_title' => 'Property Buyer & Client Directory',
                'sector_desc' => 'Manage property buyers, tenants, brokers, and site bookings',
                'row1' => [
                    ['label' => 'Total Buyers / Clients', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Inactive Leads', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active Inquiries', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Luxury / Premium', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Broker Assigned', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'WhatsApp Leads', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Residential Apartments', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'Villas & Plots', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Commercial Office', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Penthouse / Duplex', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Site Visits Done', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Booking Tokens', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'KYC Verified Buyers', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Channel Partners', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Closed Deals', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Ad Campaign Leads', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Sales Agents', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Admin Accounts', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            'healthcare' => [
                'sector_title' => 'Patient & Medical Directory',
                'sector_desc' => 'Manage patients, consulting doctors, medical staff, and appointments',
                'row1' => [
                    ['label' => 'Total Patients', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Discharged', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active In-Patients', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'VIP Priority Care', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Doctor Consults', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'WhatsApp Alerts', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Cardiology', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'Orthopedics', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'General Medicine', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Pediatrics & Dental', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Emergency OPD', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Treatment Invoices', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Medical Records Verified', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Doctor Referrals', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Admitted Cases', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Health Camp Leads', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Medical Staff', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Hospital Admins', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            'recruitment' => [
                'sector_title' => 'Candidate & Recruiter Directory',
                'sector_desc' => 'Manage job candidates, recruiters, open positions, and interviews',
                'row1' => [
                    ['label' => 'Total Candidates', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Inactive Profiles', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active Jobseekers', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Executive Search', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Interview Scheduled', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'WhatsApp Alerts', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Software & Tech', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'Sales & Marketing', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Finance & Accounts', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Operations & HR', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Shortlisted Profiles', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Placement Fees', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Background Verified', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Agency Referrals', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Offers Accepted', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Campus Hiring', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Recruiters / Staff', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'HR Admins', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            'matrimonial' => [
                'sector_title' => 'Community Member & Bio-Data Directory',
                'sector_desc' => 'Manage community bio-datas, family profiles, and matchmakers',
                'row1' => [
                    ['label' => 'Total Profiles', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Suspended User', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active Users', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'VIP Service', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Mediator Service', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'Whatsapp Service', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Jain Community', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'Hindu Community', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Other Community', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Baniya (Maheshwari & Agrawal)', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Team Data', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Payment History', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Verified Biodata', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Referral Users', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Added Users', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Promotion Users', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Staff Users', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Admin Users', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
            default => [
                'sector_title' => 'Client & Team Directory',
                'sector_desc' => 'Manage organization accounts, client leads, and team access',
                'row1' => [
                    ['label' => 'Total Contacts', 'count' => max($totalUsers, $totalContacts), 'bg' => 'bg-indigo-100 text-indigo-800'],
                    ['label' => 'Suspended Users', 'count' => $suspendedUsers, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Active Accounts', 'count' => max($activeUsers, 1), 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Enterprise Tier', 'count' => 0, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Account Managers', 'count' => $staffCount, 'bg' => 'bg-rose-100 text-rose-800'],
                    ['label' => 'WhatsApp Channel', 'count' => $totalContacts, 'bg' => 'bg-emerald-100 text-emerald-800'],
                ],
                'row2' => [
                    ['label' => 'Qualified Prospects', 'ratio' => '0/0', 'bg' => 'bg-orange-100 text-orange-800'],
                    ['label' => 'Proposal Sent', 'ratio' => '0/0', 'bg' => 'bg-emerald-100 text-emerald-800'],
                    ['label' => 'Contract Negotiation', 'ratio' => '0/0', 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Closed Won Deals', 'ratio' => '0/0', 'bg' => 'bg-yellow-100 text-yellow-800'],
                    ['label' => 'Active Projects', 'ratio' => '0/0', 'bg' => 'bg-amber-100 text-amber-800'],
                    ['label' => 'Invoices Paid', 'count' => $totalPlans, 'bg' => 'bg-teal-100 text-teal-800'],
                ],
                'row3' => [
                    ['label' => 'Verified Records', 'count' => $totalContacts, 'bg' => 'bg-cyan-100 text-cyan-800'],
                    ['label' => 'Referral Leads', 'count' => 0, 'bg' => 'bg-slate-200 text-slate-700'],
                    ['label' => 'Converted Clients', 'count' => $totalDeals, 'bg' => 'bg-purple-100 text-purple-800'],
                    ['label' => 'Marketing Campaigns', 'count' => 0, 'bg' => 'bg-emerald-100/70 text-emerald-800'],
                    ['label' => 'Support Staff', 'count' => $staffCount, 'bg' => 'bg-sky-100 text-sky-800'],
                    ['label' => 'Admin Accounts', 'count' => $adminCount, 'bg' => 'bg-indigo-100 text-indigo-800'],
                ],
            ],
        };

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
