<?php

namespace App\Actions\Deals;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateDealStageAction
{
    /**
     * Atomically update a deal's stage, log activity, and auto-generate follow-up task if needed.
     */
    public function execute(Deal $deal, int $targetStageId, ?int $userId = null): Deal
    {
        return DB::transaction(function () use ($deal, $targetStageId, $userId) {
            $previousStage = $deal->stage;
            $newStage = PipelineStage::findOrFail($targetStageId);

            if ($previousStage->id === $newStage->id) {
                return $deal;
            }

            // Update Deal record
            $deal->update([
                'stage_id' => $newStage->id,
                'closed_at' => in_array($newStage->stage_type, ['won', 'lost']) ? now() : null,
            ]);

            // Audit Timeline Activity Log
            Activity::create([
                'user_id' => $userId,
                'subject_type' => Deal::class,
                'subject_id' => $deal->id,
                'type' => 'stage_changed',
                'description' => "Moved deal '{$deal->title}' from stage '{$previousStage->name}' to '{$newStage->name}'",
                'properties' => [
                    'previous_stage_id' => $previousStage->id,
                    'previous_stage_name' => $previousStage->name,
                    'new_stage_id' => $newStage->id,
                    'new_stage_name' => $newStage->name,
                    'deal_value' => $deal->value,
                ],
            ]);

            // Automated Task Generation on Stage Change
            if ($newStage->stage_type === 'open') {
                Task::create([
                    'title' => "Follow up on deal: {$deal->title}",
                    'description' => "Stage moved to {$newStage->name}. Touch base with client.",
                    'due_at' => now()->addDays(2),
                    'priority' => 'medium',
                    'status' => 'pending',
                    'assigned_to' => $deal->assigned_to ?? $userId,
                    'created_by' => $userId ?? $deal->assigned_to ?? 1,
                    'taskable_type' => Deal::class,
                    'taskable_id' => $deal->id,
                ]);
            }

            return $deal->load(['stage', 'company', 'contact', 'assignee']);
        });
    }
}
