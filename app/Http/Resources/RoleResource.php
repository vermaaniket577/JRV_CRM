<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions_count' => $this->permissions_count ?? $this->permissions->count(),
            'users_count' => $this->users_count ?? $this->users->count(),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions->pluck('id')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
