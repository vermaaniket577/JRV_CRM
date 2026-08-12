<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MemberSearchFilter
{
    public static function apply(Request $request): Builder
    {
        $query = Member::with(['matchmaker', 'documents', 'preferences', 'activeSubscription']);

        // Gender Filter
        if ($gender = $request->query('gender')) {
            $query->where('gender', $gender);
        }

        // Age Range Filter
        if ($ageMin = $request->query('age_min')) {
            $query->where('age', '>=', (int) $ageMin);
        }
        if ($ageMax = $request->query('age_max')) {
            $query->where('age', '<=', (int) $ageMax);
        }

        // Height Range Filter (cm)
        if ($heightMin = $request->query('height_min')) {
            $query->where('height_cm', '>=', (int) $heightMin);
        }
        if ($heightMax = $request->query('height_max')) {
            $query->where('height_cm', '<=', (int) $heightMax);
        }

        // Community, Caste & Gotra Filters
        if ($caste = $request->query('caste')) {
            $query->where('caste', 'like', "%{$caste}%");
        }
        if ($subCaste = $request->query('sub_caste')) {
            $query->where('sub_caste', 'like', "%{$subCaste}%");
        }
        if ($gotra = $request->query('gotra')) {
            $query->where('gotra', 'like', "%{$gotra}%");
        }

        // Education & Profession Filters
        if ($education = $request->query('education')) {
            $query->where('education_level', 'like', "%{$education}%");
        }
        if ($occupation = $request->query('occupation')) {
            $query->where('occupation_type', 'like', "%{$occupation}%");
        }
        if ($minIncome = $request->query('min_income')) {
            $query->where('annual_income', '>=', (float) $minIncome);
        }

        // Geolocation Filters
        if ($state = $request->query('state')) {
            $query->where('state', 'like', "%{$state}%");
        }
        if ($city = $request->query('city')) {
            $query->where('city', 'like', "%{$city}%");
        }

        // Verification Status Filter
        if ($verification = $request->query('verification_status')) {
            $query->where('verification_status', $verification);
        }

        // Text Search (Name, Phone, Member Code)
        if ($search = $request->query('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }
}
