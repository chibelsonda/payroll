<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends CompanyScopedModel
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'date',
        'type',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the company that owns this holiday
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
