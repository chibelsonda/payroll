<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'default_shift_start',
        'default_break_start',
        'default_break_end',
        'default_shift_end',
        'max_shift_hours',
        'auto_close_missing_out',
        'auto_deduct_break',
        'enable_auto_correction',
    ];

    protected $casts = [
        'max_shift_hours' => 'integer',
        'auto_close_missing_out' => 'boolean',
        'auto_deduct_break' => 'boolean',
        'enable_auto_correction' => 'boolean',
    ];

    // Note: default_break_start and default_break_end are TIME columns
    // They are returned as strings from the database (e.g., "12:00:00")
    // No casting needed as they're already in the correct format

    /**
     * Get default settings (used when company has no custom settings)
     */
    public static function getDefaults(): array
    {
        return [
            'default_shift_start' => '08:00:00',
            'default_break_start' => '12:00:00',
            'default_break_end' => '13:00:00',
            'default_shift_end' => '17:00:00',
            'max_shift_hours' => 8,
            'auto_close_missing_out' => true,
            'auto_deduct_break' => true,
            'enable_auto_correction' => true, // Master switch for all auto-corrections
        ];
    }

    /**
     * Get the company that owns these settings
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
