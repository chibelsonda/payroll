<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get role from Spatie Permission (company-scoped)
        $role = 'employee'; // Default
        if ($this->relationLoaded('roles') && $this->roles->isNotEmpty()) {
            $role = $this->roles->first()->name;
        } elseif ($this->id) {
            // Fallback: get role from Spatie (may be company-scoped)
            $roleName = $this->getRoleNames()->first();
            if ($roleName) {
                $role = $roleName;
            }
        }

        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name, // Full name accessor
            'email' => $this->email,
            'has_company' => $this->company_id !== null, // Boolean flag instead of exposing company_id
            'role' => $role,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'uuid' => $this->employee->uuid,
                    'employee_no' => $this->employee->employee_no,
                    'created_at' => $this->employee->created_at?->toIso8601String(),
                    'updated_at' => $this->employee->updated_at?->toIso8601String(),
                ];
            }),
        ];
    }
}
