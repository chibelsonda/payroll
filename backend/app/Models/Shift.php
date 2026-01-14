<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends CompanyScopedModel
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'break_duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns this shift
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
