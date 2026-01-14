<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the company that owns this subscription
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all payments for this subscription
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->ends_at && 
               $this->ends_at->isFuture();
    }

    /**
     * Check if subscription is trialing
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }
}
