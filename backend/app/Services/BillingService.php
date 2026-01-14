<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\DTOs\PaymentGatewayCheckoutResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentService $paymentService
    ) {}

    /**
     * Get all active plans
     */
    public function getActivePlans()
    {
        return Plan::where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    /**
     * Find plan by UUID
     */
    public function findPlanByUuid(string $uuid): ?Plan
    {
        return Plan::where('uuid', $uuid)->first();
    }

    /**
     * Get company's current subscription
     */
    public function getCompanySubscription(Company $company): ?Subscription
    {
        return Subscription::where('company_id', $company->id)
            ->with(['plan'])
            ->latest()
            ->first();
    }

    /**
     * Create a new subscription and initiate payment
     * 
     * This method follows OCP - it uses PaymentGatewayManager to resolve gateways
     * without knowing about specific payment methods
     */
    public function subscribe(Company $company, Plan $plan, string $provider, string $method, array $options = []): array
    {
        return DB::transaction(function () use ($company, $plan, $provider, $method, $options) {
            // Create subscription
            $subscription = Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
            ]);

            // Calculate subscription dates
            $startsAt = Carbon::now();
            $endsAt = $plan->billing_cycle === 'yearly' 
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $subscription->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            // Create payment record
            $payment = $this->paymentService->createPayment([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'provider' => $provider,
                'method' => $method,
                'amount' => $plan->price,
                'currency' => 'PHP',
                'status' => 'pending',
            ]);

            // Resolve gateway using PaymentGatewayManager (OCP-compliant)
            $gateway = $this->gatewayManager->resolve($provider, $method);

            // Create checkout session
            $checkoutResponse = $gateway->createCheckout($payment, $options);

            // Update payment with checkout details
            $payment->update([
                'checkout_url' => $checkoutResponse->checkoutUrl,
                'provider_reference_id' => $checkoutResponse->referenceId,
                'metadata' => $checkoutResponse->metadata,
            ]);

            return [
                'subscription' => $subscription->fresh(['plan']),
                'payment' => $payment->fresh(),
                'checkout_url' => $checkoutResponse->checkoutUrl,
            ];
        });
    }

    /**
     * Process webhook and update subscription/payment status
     * 
     * This method follows OCP - it uses PaymentGatewayManager to resolve gateways
     */
    public function processWebhook(string $provider, string $method, $request): array
    {
        return DB::transaction(function () use ($provider, $method, $request) {
            // Resolve gateway using PaymentGatewayManager (OCP-compliant)
            $gateway = $this->gatewayManager->resolve($provider, $method);

            // Verify webhook
            $webhookResult = $gateway->verifyWebhook($request);

            // Find payment by provider reference ID
            $payment = \App\Models\Payment::where('provider_reference_id', $webhookResult->referenceId)
                ->where('provider', $provider)
                ->first();

            if (!$payment) {
                throw new \RuntimeException("Payment not found for reference ID: {$webhookResult->referenceId}");
            }

            // Update payment status
            $payment->update([
                'status' => $webhookResult->status,
                'paid_at' => $webhookResult->paidAt,
            ]);

            // Update subscription if payment is paid
            if ($webhookResult->isPaid() && $payment->subscription) {
                $subscription = $payment->subscription;
                $subscription->update([
                    'status' => 'active',
                    'starts_at' => Carbon::now(),
                    'ends_at' => $subscription->plan->billing_cycle === 'yearly'
                        ? Carbon::now()->addYear()
                        : Carbon::now()->addMonth(),
                ]);
            }

            return [
                'payment' => $payment->fresh(['subscription.plan']),
                'subscription' => $payment->subscription?->fresh(['plan']),
            ];
        });
    }

    /**
     * Get company's payment history
     */
    public function getCompanyPayments(Company $company, int $perPage = 15)
    {
        return \App\Models\Payment::where('company_id', $company->id)
            ->with(['subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
