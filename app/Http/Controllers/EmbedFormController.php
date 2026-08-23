<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Member;
use App\Models\MemberPreference;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmbedFormController extends Controller
{
    public function show(Request $request): Response
    {
        $tenantId = $request->query('tenant_id') 
            ?? $request->query('tenant') 
            ?? session('tenant_id') 
            ?? $request->user()?->tenant_id;
            
        if (!$tenantId) {
            $tenantId = 7; // Fallback active tenant (e.g. admissions dekho)
        }

        $tenant = Tenant::with('industry')->find($tenantId);

        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'education', $tenantId);
        $industryName = $tenant?->industry?->name ?? TenantSetting::getByKey('industry_name', 'Education & Training', $tenantId);
        $industryIcon = $tenant?->industry?->icon ?? TenantSetting::getByKey('business_icon', '🎓', $tenantId);
        $brandColor = TenantSetting::getByKey('brand_color', '#dc2626', $tenantId);
        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);
        $businessIcon = TenantSetting::getByKey('business_icon', $industryIcon, $tenantId);

        return Inertia::render('Embed/RegisterForm', [
            'apiKey' => $request->query('api_key'),
            'tenantId' => $tenantId,
            'industry' => [
                'slug' => $industrySlug,
                'name' => $industryName,
                'icon' => $industryIcon,
            ],
            'business' => [
                'name' => $businessName,
                'icon' => $businessIcon,
                'brand_color' => $brandColor,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $request->input('tenant_id') ?? session('tenant_id') ?? $request->user()?->tenant_id ?? 7;
        $tenant = Tenant::with('industry')->find($tenantId);
        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'education', $tenantId);
        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'CRM Portal', $tenantId);

        // 1. EDUCATION & TRAINING
        if ($industrySlug === 'education') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'course_interested' => ['required', 'string', 'max:255'],
                'qualification' => ['nullable', 'string', 'max:255'],
                'percentage' => ['nullable', 'string', 'max:50'],
                'admission_year' => ['nullable', 'string', 'max:50'],
                'city' => ['nullable', 'string', 'max:255'],
                'message' => ['nullable', 'string'],
            ]);

            Contact::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'] ?: 'Student Lead',
                    'phone' => $validated['phone'],
                    'city' => $validated['city'] ?? 'Mumbai',
                    'source' => 'Website Admission Form (' . $validated['course_interested'] . ')',
                ]
            );

            return redirect()->back()->with('success', 'Your admission inquiry has been received! Our senior education counselor from ' . $businessName . ' will contact you shortly.');
        }

        // 2. REAL ESTATE
        if ($industrySlug === 'real-estate') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'inquiry_type' => ['nullable', 'string'],
                'property_type' => ['nullable', 'string'],
                'bedrooms' => ['nullable', 'string'],
                'budget' => ['nullable', 'numeric'],
                'city' => ['nullable', 'string'],
                'locality' => ['nullable', 'string'],
                'message' => ['nullable', 'string'],
            ]);

            Contact::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'] ?: 'Property Lead',
                    'phone' => $validated['phone'],
                    'city' => $validated['city'] ?? 'Mumbai',
                    'source' => 'Website Embed Form (' . ($validated['inquiry_type'] ?? 'Property Inquiry') . ')',
                ]
            );

            return redirect()->back()->with('success', 'Your property inquiry has been received! Our real estate team will connect with you shortly.');
        }

        // 3. HEALTHCARE & CLINIC
        if ($industrySlug === 'healthcare') {
            $validated = $request->validate([
                'patient_name' => ['required', 'string', 'max:255'],
                'age_gender' => ['nullable', 'string', 'max:50'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'department' => ['required', 'string', 'max:255'],
                'appointment_type' => ['nullable', 'string', 'max:255'],
                'preferred_date' => ['nullable', 'string', 'max:50'],
                'city' => ['nullable', 'string', 'max:255'],
                'symptoms' => ['nullable', 'string'],
            ]);

            $parts = explode(' ', $validated['patient_name'], 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? 'Patient';

            Contact::updateOrCreate(
                ['email' => $validated['email'] ?: ('patient_' . time() . '@clinic-lead.test')],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $validated['phone'],
                    'city' => $validated['city'] ?? 'Mumbai',
                    'source' => 'Website Healthcare Appointment (' . $validated['department'] . ')',
                ]
            );

            return redirect()->back()->with('success', 'Your appointment request has been scheduled! Our clinic desk will call to confirm your appointment slot.');
        }

        // 4. RECRUITMENT & HR
        if ($industrySlug === 'recruitment') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'job_title' => ['required', 'string', 'max:255'],
                'experience_years' => ['nullable', 'string', 'max:50'],
                'current_company' => ['nullable', 'string', 'max:255'],
                'notice_period' => ['nullable', 'string', 'max:50'],
                'expected_salary' => ['nullable', 'string', 'max:50'],
                'city' => ['nullable', 'string', 'max:255'],
                'skills_summary' => ['nullable', 'string'],
            ]);

            Contact::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'] ?: 'Applicant',
                    'phone' => $validated['phone'],
                    'city' => $validated['city'] ?? 'Mumbai',
                    'source' => 'Website Job Application (' . $validated['job_title'] . ')',
                ]
            );

            return redirect()->back()->with('success', 'Your candidate profile has been submitted! Our recruitment team will review and contact you for matching openings.');
        }

        // 5. IT & SOFTWARE
        if ($industrySlug === 'it-software') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'service_required' => ['required', 'string', 'max:255'],
                'budget' => ['nullable', 'string', 'max:50'],
                'timeline' => ['nullable', 'string', 'max:50'],
                'city' => ['nullable', 'string', 'max:255'],
                'message' => ['nullable', 'string'],
            ]);

            Contact::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['company_name'] ?: 'Client',
                    'phone' => $validated['phone'],
                    'city' => $validated['city'] ?? 'Mumbai',
                    'source' => 'Website IT Project Inquiry (' . $validated['service_required'] . ')',
                ]
            );

            return redirect()->back()->with('success', 'Your project requirements have been received! Our tech team will reach out with an estimate.');
        }

        // 6. MATRIMONIAL & MATCHMAKING
        if ($industrySlug === 'matrimonial') {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'gender' => ['required', 'string', 'in:Male,Female,Other'],
                'date_of_birth' => ['required', 'date'],
                'height_cm' => ['nullable', 'integer'],
                'marital_status' => ['required', 'string'],
                'religion' => ['required', 'string'],
                'caste' => ['required', 'string'],
                'sub_caste' => ['nullable', 'string'],
                'gotra' => ['nullable', 'string'],
                'mother_gotra' => ['nullable', 'string'],
                'education_level' => ['nullable', 'string'],
                'occupation_type' => ['nullable', 'string'],
                'annual_income' => ['nullable', 'numeric'],
                'phone' => ['required', 'string'],
                'email' => ['required', 'email'],
                'state' => ['required', 'string'],
                'city' => ['required', 'string'],
            ]);

            $dob = new \DateTime($validated['date_of_birth']);
            $age = $dob->diff(new \DateTime())->y;

            $member = Member::updateOrCreate(
                ['email' => $validated['email']],
                array_merge($validated, [
                    'member_code' => 'JSM-' . strtoupper(substr($validated['gender'], 0, 1)) . '-' . mt_rand(10000, 99999),
                    'age' => $age,
                    'verification_status' => 'Pending Review',
                    'status' => 'Active',
                ])
            );

            MemberPreference::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'age_min' => max(18, $age - 5),
                    'age_max' => $age + 5,
                    'caste' => $validated['caste'],
                    'preferred_state' => $validated['state'],
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

            return redirect()->back()->with('success', 'Your bio-data profile has been submitted successfully.');
        }

        // 7. GENERIC / UNIVERSAL INQUIRY FALLBACK
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'inquiry_type' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
        ]);

        Contact::updateOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?: 'Lead',
                'phone' => $validated['phone'],
                'city' => $validated['city'] ?? 'Mumbai',
                'source' => 'Website Inquiry Form (' . ($validated['inquiry_type'] ?? 'General Inquiry') . ')',
            ]
        );

        return redirect()->back()->with('success', 'Thank you! Your inquiry has been received. Our team will contact you shortly.');
    }
}

