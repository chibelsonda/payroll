<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'attendance';

    protected $fillable = [
        'uuid',
        'employee_id',
        'date',
        'hours_worked',
        'status',
        'is_incomplete',
        'needs_review',
        'is_auto_corrected',
        'is_locked',
        'correction_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
        'is_incomplete' => 'boolean',
        'needs_review' => 'boolean',
        'is_auto_corrected' => 'boolean',
        'is_locked' => 'boolean',
        'status' => AttendanceStatus::class,
    ];

    /**
     * Get the employee that owns this attendance record
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the attendance logs for this attendance record
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'employee_id', 'employee_id')
            ->whereDate('log_time', $this->date);
    }
}
