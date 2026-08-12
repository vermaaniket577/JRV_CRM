<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'value' => (float) $this->value,
            'formatted_value' => '₹' . number_format($this->value, 2),
            'currency' => 'INR',
            'pipeline_id' => $this->pipeline_id,
            'stage_id' => $this->stage_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'contact' => $this->whenLoaded('contact', fn () => [
                'id' => $this->contact->id,
                'name' => $this->contact->full_name,
                'email' => $this->contact->email,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'avatar_path' => $this->assignee->avatar_path,
            ]),
            'expected_close_date' => $this->expected_close_date?->format('Y-m-d'),
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
