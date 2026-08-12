<?php

namespace App\Http\Controllers;

use App\Actions\Deals\UpdateDealStageAction;
use App\Http\Requests\Deals\UpdateDealStageRequest;
use App\Http\Resources\PipelineResource;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
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
        $pipelineId = $request->query('pipeline_id')
            ? (int) $request->query('pipeline_id')
            : Pipeline::where('is_default', true)->value('id') ?? Pipeline::first()?->id;

        $pipeline = Pipeline::with([
            'stages' => fn ($q) => $q->orderBy('display_order'),
            'stages.deals' => fn ($q) => $q->with(['company', 'contact', 'assignee'])->latest(),
        ])->findOrFail($pipelineId);

        $allPipelines = Pipeline::where('is_active', true)->get(['id', 'name', 'is_default']);

        return Inertia::render('Deals/Index', [
            'currentPipeline' => new PipelineResource($pipeline),
            'pipelines' => $allPipelines,
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
            'currency' => 'USD',
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
}
