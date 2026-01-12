<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'hours_worked',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
    ];

    /**
     * Get the employee that owns this attendance record
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
