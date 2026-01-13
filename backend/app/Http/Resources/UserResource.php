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
        // Get fresh user instance to ensure we have latest data (including newly attached companies)
        $user = $this->fresh();

        // Get role from Spatie Permission (company-scoped)
        $role = 'employee'; // Default

        // Get active company ID from middleware, or fallback to user's latest company
        $activeCompanyId = app()->bound('active_company_id') ? app('active_company_id') : null;

        // If no active company is set, use the user's most recently created company
        if (!$activeCompanyId && $user->id) {
            $latestCompany = $user->companies()->orderBy('created_at', 'desc')->first();
            if ($latestCompany) {
                $activeCompanyId = $latestCompany->id;
            }
        }

        // Set Spatie team context and retrieve role
        if ($activeCompanyId && $user->id) {
            $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
            $previousTeamId = $registrar->getPermissionsTeamId();

            try {
                $registrar->setPermissionsTeamId($activeCompanyId);
                $registrar->forgetCachedPermissions();

                $roleNames = $user->getRoleNames();
                if ($roleNames->isNotEmpty()) {
                    $spatieRole = $roleNames->first();
                    // Map 'user' back to 'employee' for frontend consistency
                    // (Spatie stores 'employee' invitations as 'user' role)
                    $role = $spatieRole === 'user' ? 'employee' : $spatieRole;
                } else {
                    // Fallback: check database directly if Spatie cache is stale
                    $dbRole = DB::table('model_has_roles as mhr')
                        ->where('mhr.model_id', $user->id)
                        ->where('mhr.model_type', 'App\Models\User')
                        ->where('mhr.company_id', $activeCompanyId)
                        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                        ->select('r.name')
                        ->first();

                    if ($dbRole) {
                        $spatieRole = $dbRole->name;
                        // Map 'user' back to 'employee' for frontend consistency
                        $role = $spatieRole === 'user' ? 'employee' : $spatieRole;
                    }
                }
            } finally {
                $registrar->setPermissionsTeamId($previousTeamId);
            }
        }

        // Check if user belongs to any company (using fresh model)
        $hasCompany = $user->companies()->exists();

        return [
            'uuid' => $user->uuid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name, // Full name accessor
            'email' => $user->email,
            'has_company' => $hasCompany, // Check if user belongs to any company (using fresh model)
            'role' => $role,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'employee' => $user->relationLoaded('employee') && $user->employee ? [
                'uuid' => $user->employee->uuid,
                'employee_no' => $user->employee->employee_no,
                'created_at' => $user->employee->created_at?->toIso8601String(),
                'updated_at' => $user->employee->updated_at?->toIso8601String(),
            ] : null,
        ];
    }
}
