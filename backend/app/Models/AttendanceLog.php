<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'employee_id',
        'log_time',
        'type',
        'is_auto_corrected',
        'correction_reason',
    ];

    protected $casts = [
        'log_time' => 'datetime',
        'is_auto_corrected' => 'boolean',
    ];

    /**
     * Get the employee that owns this attendance log
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
