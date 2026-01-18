<?php

namespace App\Models;

use App\Enums\EarningType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEarning extends Model
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
        'type' => EarningType::class,
    ];

    /**
     * Get the payroll that owns this earning
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
