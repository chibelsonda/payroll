<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends CompanyScopedModel
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'period_start',
        'period_end',
        'pay_date',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'pay_date' => 'date',
    ];

    /**
     * Get the company that owns the payroll run
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all payrolls for this payroll run
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
