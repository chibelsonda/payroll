<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Models\Subscription;
use Carbon\Carbon;
use DomainException;

class SubscriptionService
{
    /**
     * First day of the current billing month
     */
    public function currentBillingMonth(): string
    {
        return Carbon::now()->startOfMonth()->toDateString();
    }

    /**
     * Assert whether the company can subscribe to the given plan
     *
     * @throws DomainException
     */
    public function assertCanSubscribe(int $companyId, int $planId): void
    {
        $billingMonth = $this->currentBillingMonth();

        $existing = Subscription::where('company_id', $companyId)
            ->where('billing_month', $billingMonth)
            ->whereIn('status', ['active', 'pending'])
            ->with('plan')
            ->first();

        if (! $existing) {
            return;
        }

        if ((int) $existing->plan_id === $planId) {
            throw new DomainException('You are already subscribed to this plan for this month.');
        }

        throw new DomainException('You can upgrade your plan instead.');
    }

    /**
     * Get the current billing month subscription for a company
     */
    public function getCurrentSubscription(int $companyId): ?Subscription
    {
        $billingMonth = $this->currentBillingMonth();

        return Subscription::where('company_id', $companyId)
            ->where('billing_month', $billingMonth)
            ->with('plan')
            ->latest()
            ->first();
    }
}
