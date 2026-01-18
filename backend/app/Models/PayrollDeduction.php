<?php

namespace App\Models;

use App\Enums\DeductionType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeduction extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'payroll_id',
        'type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => DeductionType::class,
    ];

    /**
     * Get the payroll that owns this deduction
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
