<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $this->ends_at?->format('Y-m-d H:i:s'),
            'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d H:i:s'),
            'plan' => $this->whenLoaded('plan', fn() => new PlanResource($this->plan)),
            'plan_uuid' => $this->plan?->uuid,
            'company_uuid' => $this->company?->uuid,
            'is_active' => $this->isActive(),
            'is_trialing' => $this->isTrialing(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
