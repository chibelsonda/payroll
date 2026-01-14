<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity
     *
     * @param string $action
     * @param Model|null $subject
     * @param string|null $description
     * @param array|null $changes
     * @return ActivityLog
     */
    public function log(string $action, ?Model $subject = null, ?string $description = null, ?array $changes = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log payroll generation
     *
     * @param Model $payrollRun
     * @param int $employeeCount
     * @return ActivityLog
     */
    public function logPayrollGeneration(Model $payrollRun, int $employeeCount): ActivityLog
    {
        return $this->log(
            'payroll_generated',
            $payrollRun,
            "Generated payroll for {$employeeCount} employees",
            ['employee_count' => $employeeCount]
        );
    }

    /**
     * Log payroll finalization
     *
     * @param Model $payrollRun
     * @return ActivityLog
     */
    public function logPayrollFinalization(Model $payrollRun): ActivityLog
    {
        return $this->log(
            'payroll_finalized',
            $payrollRun,
            'Payroll finalized and locked'
        );
    }

    /**
     * Log model creation
     *
     * @param Model $model
     * @return ActivityLog
     */
    public function logCreation(Model $model): ActivityLog
    {
        return $this->log(
            'created',
            $model,
            "Created " . class_basename($model)
        );
    }

    /**
     * Log model update
     *
     * @param Model $model
     * @param array $changes
     * @return ActivityLog
     */
    public function logUpdate(Model $model, array $changes): ActivityLog
    {
        return $this->log(
            'updated',
            $model,
            "Updated " . class_basename($model),
            $changes
        );
    }

    /**
     * Log model deletion
     *
     * @param Model $model
     * @return ActivityLog
     */
    public function logDeletion(Model $model): ActivityLog
    {
        return $this->log(
            'deleted',
            $model,
            "Deleted " . class_basename($model)
        );
    }
}
