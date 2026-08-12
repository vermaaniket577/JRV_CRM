<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberPreference;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmbedFormController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Embed/RegisterForm', [
            'apiKey' => $request->query('api_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
            'email' => ['required', 'email', 'unique:members,email'],
            'state' => ['required', 'string'],
            'city' => ['required', 'string'],
        ]);

        $dob = new \DateTime($validated['date_of_birth']);
        $age = $dob->diff(new \DateTime())->y;

        $member = Member::create(array_merge($validated, [
            'member_code' => 'JSM-' . strtoupper(substr($validated['gender'], 0, 1)) . '-' . mt_rand(10000, 99999),
            'age' => $age,
            'verification_status' => 'Pending Review',
            'status' => 'Active',
        ]));

        MemberPreference::create([
            'member_id' => $member->id,
            'age_min' => max(18, $age - 5),
            'age_max' => $age + 5,
            'caste' => $validated['caste'],
            'preferred_state' => $validated['state'],
        ]);

        Subscription::create([
            'member_id' => $member->id,
            'plan_tier' => 'Free',
            'amount_paid' => 0.00,
            'currency' => 'INR',
            'payment_status' => 'Paid',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        return redirect()->back()->with('success', 'Your bio-data profile has been submitted successfully.');
    }
}
