<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'gross_pay',
        'total_deductions',
        'net_pay',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    /**
     * Get the payroll run that owns this payroll
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * Get the employee for this payroll
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get all earnings for this payroll
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollEarning::class);
    }

    /**
     * Get all deductions for this payroll
     */
    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}
