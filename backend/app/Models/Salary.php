<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'employee_id',
        'amount',
        'effective_from',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
    ];

    /**
     * Get the employee that owns this salary
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
