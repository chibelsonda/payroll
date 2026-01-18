<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'uuid' => $this->user->uuid ?? null,
                    'first_name' => $this->user->first_name ?? null,
                    'last_name' => $this->user->last_name ?? null,
                    'email' => $this->user->email ?? null,
                ];
            }),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'action' => $this->action,
            'description' => $this->description,
            'changes' => $this->changes,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
