<?php

namespace App\Http\Controllers;

use App\Actions\Deals\UpdateDealStageAction;
use App\Http\Requests\Deals\UpdateDealStageRequest;
use App\Http\Resources\PipelineResource;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Services\IndustrySchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    /**
     * Display the Kanban Board view for the deals pipeline.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryConfig = IndustrySchemaService::getIndustryConfig($industrySlug);

        // Find or create sector-appropriate pipeline
        $pipeline = $this->resolvePipelineForIndustry($request->query('pipeline_id'), $industrySlug, $tenantId);

        $allPipelines = Pipeline::where('is_active', true)->get(['id', 'name', 'is_default']);

        // Directly format the pipeline data to avoid JsonResource 'data' key nesting issues in Vue
        $formattedPipeline = [
            'id' => $pipeline->id,
            'name' => $pipeline->name,
            'is_default' => (bool) $pipeline->is_default,
            'stages' => $pipeline->stages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'display_order' => $stage->display_order,
                    'win_probability' => $stage->win_probability,
                    'stage_type' => $stage->stage_type,
                    'deals_count' => $stage->deals->count(),
                    'stage_value' => (float) $stage->deals->sum('value'),
                    'formatted_stage_value' => '₹' . number_format($stage->deals->sum('value'), 2),
                    'deals' => $stage->deals->map(function ($deal) {
                        return [
                            'id' => $deal->id,
                            'title' => $deal->title,
                            'value' => (float) $deal->value,
                            'formatted_value' => '₹' . number_format($deal->value, 2),
                            'currency' => $deal->currency ?? 'INR',
                            'pipeline_id' => $deal->pipeline_id,
                            'stage_id' => $deal->stage_id,
                            'company' => $deal->company ? ['id' => $deal->company->id, 'name' => $deal->company->name] : null,
                            'contact' => $deal->contact ? ['id' => $deal->contact->id, 'name' => $deal->contact->name, 'email' => $deal->contact->email] : null,
                            'assignee' => $deal->assignee ? ['id' => $deal->assignee->id, 'name' => $deal->assignee->name] : null,
                            'expected_close_date' => $deal->expected_close_date?->format('M d, Y'),
                            'created_at' => $deal->created_at?->diffForHumans() ?? 'Just now',
                        ];
                    }),
                ];
            })->values()->all(),
        ];

        return Inertia::render('Deals/Index', [
            'currentPipeline' => $formattedPipeline,
            'pipelines' => $allPipelines,
            'industryConfig' => $industryConfig,
        ]);
    }

    /**
     * Create a new deal opportunity.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
            'stage_id' => ['required', 'integer', 'exists:pipeline_stages,id'],
        ]);

        $stage = PipelineStage::findOrFail($validated['stage_id']);

        Deal::create([
            'title' => $validated['title'],
            'value' => $validated['value'],
            'currency' => 'INR',
            'pipeline_id' => $stage->pipeline_id,
            'stage_id' => $stage->id,
            'assigned_to' => auth()->id() ?? 1,
            'expected_close_date' => now()->addDays(14),
        ]);

        return redirect()->back()->with('success', 'Opportunity created successfully.');
    }

    /**
     * Handle drag-and-drop pipeline stage updates.
     */
    public function updateStage(UpdateDealStageRequest $request, Deal $deal, UpdateDealStageAction $action): JsonResponse
    {
        $updatedDeal = $action->execute(
            deal: $deal,
            targetStageId: $request->validated('stage_id'),
            userId: auth()->id()
        );

        return response()->json([
            'message' => 'Deal stage updated successfully.',
            'deal' => [
                'id' => $updatedDeal->id,
                'stage_id' => $updatedDeal->stage_id,
                'stage_name' => $updatedDeal->stage->name,
                'value' => (float) $updatedDeal->value,
            ],
        ]);
    }

    /**
     * Resolve or initialize sector pipeline
     */
    private function resolvePipelineForIndustry(?string $requestedId, string $industrySlug, ?int $tenantId): Pipeline
    {
        if ($requestedId) {
            $pipeline = Pipeline::with([
                'stages' => fn ($q) => $q->orderBy('display_order'),
                'stages.deals' => fn ($q) => $q->with(['company', 'contact', 'assignee'])->latest(),
            ])->find((int) $requestedId);

            if ($pipeline && $pipeline->stages->isNotEmpty()) {
                return $pipeline;
            }
        }

        // Try to find an industry matching pipeline name
        $nameKeyword = match ($industrySlug) {
            'insurance' => 'Insurance',
            'education' => 'Education',
            'healthcare' => 'Healthcare',
            'real-estate' => 'Real Estate',
            'recruitment' => 'Recruitment',
            default => 'Sales',
        };

        $pipeline = Pipeline::with([
            'stages' => fn ($q) => $q->orderBy('display_order'),
            'stages.deals' => fn ($q) => $q->with(['company', 'contact', 'assignee'])->latest(),
        ])->where('name', 'like', "%{$nameKeyword}%")->first();

        // If pipeline not found or has no stages, create or ensure standard pipeline
        if (!$pipeline || $pipeline->stages->isEmpty()) {
            $pipeline = $this->createDefaultPipelineForIndustry($industrySlug);
            $pipeline->load([
                'stages' => fn ($q) => $q->orderBy('display_order'),
                'stages.deals' => fn ($q) => $q->with(['company', 'contact', 'assignee'])->latest(),
            ]);
        }

        return $pipeline;
    }

    private function createDefaultPipelineForIndustry(string $industrySlug): Pipeline
    {
        $pipelineData = match ($industrySlug) {
            'insurance' => [
                'name' => 'Insurance Underwriting & Claims Pipeline',
                'stages' => [
                    ['name' => 'Quote & Proposal', 'order' => 1, 'win' => 10, 'type' => 'open'],
                    ['name' => 'Medical / Risk Review', 'order' => 2, 'win' => 30, 'type' => 'open'],
                    ['name' => 'Underwriter Approved', 'order' => 3, 'win' => 60, 'type' => 'open'],
                    ['name' => 'Policy Active & Bound', 'order' => 4, 'win' => 90, 'type' => 'open'],
                    ['name' => 'Settled & Renewed', 'order' => 5, 'win' => 100, 'type' => 'won'],
                    ['name' => 'Declined / Lapsed', 'order' => 6, 'win' => 0, 'type' => 'lost'],
                ],
                'starter_deals' => [
                    ['title' => 'Comprehensive Health Shield (5 Lakh Sum Insured)', 'value' => 24500, 'stage_idx' => 0],
                    ['title' => 'Commercial Fleet Motor Cover (12 Trucks)', 'value' => 180000, 'stage_idx' => 1],
                    ['title' => 'Term Life 1 Crore Family Protection', 'value' => 18900, 'stage_idx' => 3],
                ]
            ],
            'healthcare' => [
                'name' => 'Healthcare Patient Care Pipeline',
                'stages' => [
                    ['name' => 'New Patient Inquiry', 'order' => 1, 'win' => 10, 'type' => 'open'],
                    ['name' => 'Doctor Consultation Booked', 'order' => 2, 'win' => 35, 'type' => 'open'],
                    ['name' => 'Diagnostics & Lab Tests', 'order' => 3, 'win' => 60, 'type' => 'open'],
                    ['name' => 'Treatment / Surgery Scheduled', 'order' => 4, 'win' => 85, 'type' => 'open'],
                    ['name' => 'Discharged & Recovered', 'order' => 5, 'win' => 100, 'type' => 'won'],
                ],
                'starter_deals' => [
                    ['title' => 'Orthopedic Knee Replacement Package', 'value' => 175000, 'stage_idx' => 1],
                    ['title' => 'Executive Full Body Health Checkup', 'value' => 9500, 'stage_idx' => 0],
                ]
            ],
            'education' => [
                'name' => 'Education Admissions Pipeline',
                'stages' => [
                    ['name' => 'Inquiry & Lead', 'order' => 1, 'win' => 10, 'type' => 'open'],
                    ['name' => 'Counseling Session Scheduled', 'order' => 2, 'win' => 30, 'type' => 'open'],
                    ['name' => 'Application & Documents Submitted', 'order' => 3, 'win' => 60, 'type' => 'open'],
                    ['name' => 'Admission Offered', 'order' => 4, 'win' => 80, 'type' => 'open'],
                    ['name' => 'Fee Paid - Enrolled', 'order' => 5, 'win' => 100, 'type' => 'won'],
                    ['name' => 'Withdrawn / Lost', 'order' => 6, 'win' => 0, 'type' => 'lost'],
                ],
                'starter_deals' => [
                    ['title' => 'B.Tech Computer Science Seat Confirmation - Aarav Sharma', 'value' => 125000, 'stage_idx' => 2],
                    ['title' => 'MBA Finance Admissions Fee - Simran Kaur', 'value' => 250000, 'stage_idx' => 4],
                ]
            ],
            default => [
                'name' => 'Standard Opportunity Pipeline',
                'stages' => [
                    ['name' => 'New Lead', 'order' => 1, 'win' => 10, 'type' => 'open'],
                    ['name' => 'Discovery & Qualified', 'order' => 2, 'win' => 30, 'type' => 'open'],
                    ['name' => 'Proposal & Pricing Sent', 'order' => 3, 'win' => 60, 'type' => 'open'],
                    ['name' => 'Negotiation & Contract', 'order' => 4, 'win' => 80, 'type' => 'open'],
                    ['name' => 'Closed Won', 'order' => 5, 'win' => 100, 'type' => 'won'],
                    ['name' => 'Closed Lost', 'order' => 6, 'win' => 0, 'type' => 'lost'],
                ],
                'starter_deals' => [
                    ['title' => 'Enterprise CRM Annual Subscription', 'value' => 48000, 'stage_idx' => 2],
                    ['title' => 'Custom Module Implementation Contract', 'value' => 75000, 'stage_idx' => 0],
                ]
            ]
        };

        $pipeline = Pipeline::create([
            'name' => $pipelineData['name'],
            'is_default' => true,
            'is_active' => true,
        ]);

        $createdStages = [];
        foreach ($pipelineData['stages'] as $s) {
            $createdStages[] = PipelineStage::create([
                'pipeline_id' => $pipeline->id,
                'name' => $s['name'],
                'display_order' => $s['order'],
                'win_probability' => $s['win'],
                'stage_type' => $s['type'],
            ]);
        }

        foreach ($pipelineData['starter_deals'] as $d) {
            $stage = $createdStages[$d['stage_idx']] ?? $createdStages[0];
            Deal::create([
                'title' => $d['title'],
                'value' => $d['value'],
                'currency' => 'INR',
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'assigned_to' => 1,
                'expected_close_date' => now()->addDays(20),
            ]);
        }

        return $pipeline;
    }
}
