<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'subscription_id',
        'provider',
        'method',
        'provider_reference_id',
        'checkout_url',
        'amount',
        'currency',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the company that owns this payment
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription for this payment
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if payment is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
