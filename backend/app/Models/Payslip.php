<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'payroll_id',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /**
     * Get the payroll that this payslip belongs to
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
