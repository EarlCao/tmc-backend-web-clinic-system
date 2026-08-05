<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => (bool) $this->is_system,
            // Only present when the role is loaded with its permissions
            // (role detail/assignment endpoints); list endpoints rely on the
            // counts to keep the payload small.
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'permissions_count' => (int) ($this->permissions_count ?? 0),
            'users_count' => (int) ($this->users_count ?? 0),
        ];
    }
}
