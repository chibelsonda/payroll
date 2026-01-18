<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'employee_id',
        'type',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the employee that owns this leave balance
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
