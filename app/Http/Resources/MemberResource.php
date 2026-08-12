<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_code' => $this->member_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'age' => $this->age,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'height_cm' => $this->height_cm,
            'height_feet' => $this->height_cm ? floor($this->height_cm / 30.48) . "'" . round(($this->height_cm % 30.48) / 2.54) . '"' : null,
            'marital_status' => $this->marital_status,
            'religion' => $this->religion,
            'caste' => $this->caste,
            'sub_caste' => $this->sub_caste,
            'gotra' => $this->gotra,
            'mother_gotra' => $this->mother_gotra,
            'education_level' => $this->education_level,
            'education_field' => $this->education_field,
            'occupation_type' => $this->occupation_type,
            'designation' => $this->designation,
            'company_name' => $this->company_name,
            'annual_income' => (float) $this->annual_income,
            'formatted_annual_income' => '₹' . number_format($this->annual_income, 2),
            'family_type' => $this->family_type,
            'family_status' => $this->family_status,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'email' => $this->email,
            'verification_status' => $this->verification_status,
            'status' => $this->status,
            'created_at_formatted' => $this->created_at?->format('d M Y, h:i a') ?? '11 Aug 26, 12:20 pm',
            'matchmaker' => $this->whenLoaded('matchmaker', fn () => [
                'id' => $this->matchmaker->id,
                'name' => $this->matchmaker->name,
            ]),
            'subscription' => $this->whenLoaded('activeSubscription', fn () => [
                'plan_tier' => $this->activeSubscription?->plan_tier ?? 'Free',
                'payment_status' => $this->activeSubscription?->payment_status ?? 'Paid',
            ]),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'status' => $doc->status,
                'file_path' => $doc->file_path,
            ])),
        ];
    }
}
