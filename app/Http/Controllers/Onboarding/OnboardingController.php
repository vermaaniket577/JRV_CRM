<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\Industry;
use App\Models\Tenant;
use App\Services\IndustryConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function index(): Response
    {
        $industries = Industry::active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug', 'icon', 'description', 'color']);

        return Inertia::render('Onboarding/Index', [
            'industries' => $industries,
        ]);
    }

    public function getBusinessTypes(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
        ]);

        $types = BusinessType::where('industry_id', $request->industry_id)
            ->active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json($types);
    }

    public function complete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'employee_range' => ['nullable', 'string', 'max:20'],
            'crm_goals' => ['nullable', 'array'],
        ]);

        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);

        if (!$tenant) {
            return redirect('/login')->withErrors(['error' => 'Organization not found.']);
        }

        // Update tenant with onboarding data
        $tenant->update([
            'industry_id' => $validated['industry_id'],
            'business_type_id' => $validated['business_type_id'] ?? null,
            'employee_range' => $validated['employee_range'] ?? null,
            'crm_goals' => $validated['crm_goals'] ?? [],
            'onboarding_completed' => true,
        ]);

        // Provision CRM configuration based on selected industry
        $configService = new IndustryConfigurationService();
        $configService->provision($tenant);

        return redirect('/')->with('success', 'Your CRM has been configured for your business. Welcome!');
    }
}
