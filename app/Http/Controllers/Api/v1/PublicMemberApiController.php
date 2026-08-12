<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberPreference;
use App\Models\Subscription;
use App\Services\MemberSearchFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMemberApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = MemberSearchFilter::apply($request);
        $members = $query->paginate($request->query('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $members->items(),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'total' => $members->total(),
            ],
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function register(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => 'Bio-data submitted successfully via website integration.',
            'member_code' => $member->member_code,
        ], 201)->header('Access-Control-Allow-Origin', '*');
    }
}
