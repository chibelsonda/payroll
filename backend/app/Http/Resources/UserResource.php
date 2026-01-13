<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

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

        // Get active company ID from middleware, or fallback to user's latest company
        $activeCompanyId = app()->bound('active_company_id') ? app('active_company_id') : null;

        // If no active company is set, use the user's most recently created company
        if (!$activeCompanyId && $this->id) {
            $latestCompany = $this->fresh()->companies()->orderBy('created_at', 'desc')->first();
            if ($latestCompany) {
                $activeCompanyId = $latestCompany->id;
            }
        }

        // Set Spatie team context and retrieve role
        if ($activeCompanyId && $this->id) {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $previousTeamId = $registrar->getPermissionsTeamId();

            try {
                $registrar->setPermissionsTeamId($activeCompanyId);
                $registrar->forgetCachedPermissions();

                $roleNames = $this->getRoleNames();
                if ($roleNames->isNotEmpty()) {
                    $role = $roleNames->first();
                } else {
                    // Fallback: check database directly if Spatie cache is stale
                    $dbRole = DB::table('model_has_roles as mhr')
                        ->where('mhr.model_id', $this->id)
                        ->where('mhr.model_type', 'App\Models\User')
                        ->where('mhr.company_id', $activeCompanyId)
                        ->join('roles', 'mhr.role_id', '=', 'roles.id')
                        ->select('roles.name')
                        ->first();

                    if ($dbRole) {
                        $role = $dbRole->name;
                    }
                }
            } finally {
                $registrar->setPermissionsTeamId($previousTeamId);
            }
        }

        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name, // Full name accessor
            'email' => $this->email,
            'has_company' => $this->companies()->exists(), // Check if user belongs to any company
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
