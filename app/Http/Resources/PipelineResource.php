<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->is_default,
            'stages' => $this->stages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'display_order' => $stage->display_order,
                    'win_probability' => $stage->win_probability,
                    'stage_type' => $stage->stage_type,
                    'deals_count' => $stage->deals->count(),
                    'stage_value' => (float) $stage->deals->sum('value'),
                    'formatted_stage_value' => '₹' . number_format($stage->deals->sum('value'), 2),
                    'deals' => DealResource::collection($stage->deals),
                ];
            }),
        ];
    }
}
