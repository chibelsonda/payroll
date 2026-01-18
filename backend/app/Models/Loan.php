<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'employee_id',
        'amount',
        'balance',
        'start_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'start_date' => 'date',
    ];

    /**
     * Get the employee that owns this loan
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get all payments for this loan
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}
