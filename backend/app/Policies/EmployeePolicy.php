<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any models.
     * Spatie team context is set by SetActiveCompany middleware
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Determine whether the user can view the model.
     * Spatie team context is set by SetActiveCompany middleware
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole('owner') || $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Determine whether the user can create models.
     * Spatie team context is set by SetActiveCompany middleware
     */
    public function create(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Determine whether the user can update the model.
     * Spatie team context is set by SetActiveCompany middleware
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasRole('owner') || $user->hasRole('admin') || $user->hasRole('hr');
    }

    /**
     * Determine whether the user can delete the model.
     * Spatie team context is set by SetActiveCompany middleware
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole('owner') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return false;
    }
}
