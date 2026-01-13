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

        // Set Spatie team context if user has a company_id
        if ($this->company_id && $this->id) {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $previousTeamId = $registrar->getPermissionsTeamId();

            try {
                // Set team context before getting roles
                $registrar->setPermissionsTeamId($this->company_id);

                // Clear cached permissions to ensure fresh lookup
                $registrar->forgetCachedPermissions();

                // Query roles directly from the pivot table with company_id filter
                // This bypasses potential caching issues with getRoleNames()
                $userRoles = $this->roles()->wherePivot('company_id', $this->company_id)->get();
                if ($userRoles->isNotEmpty()) {
                    $role = $userRoles->first()->name;
                } else {
                    // Fallback to getRoleNames() if direct query doesn't work
                    $roleNames = $this->getRoleNames();
                    if ($roleNames->isNotEmpty()) {
                        $role = $roleNames->first();
                    }
                }
            } finally {
                // Restore previous team context
                $registrar->setPermissionsTeamId($previousTeamId);
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
