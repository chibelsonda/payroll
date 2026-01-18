<?php

namespace App\Services\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentService;
use App\Services\Payments\PaymentGatewayManager;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentInitiationService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentService $paymentService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Initiate a subscription + payment flow after asserting eligibility
     */
    public function initiate(
        Company $company,
        Plan $plan,
        string $provider,
        string $method,
        array $options = []
    ): array {
        $providerEnum = PaymentProvider::tryFrom($provider);
        $methodEnum   = PaymentMethod::tryFrom($method);

        if (! $providerEnum || ! $methodEnum) {
            throw new InvalidArgumentException('Invalid payment provider or method');
        }

        // Guard against duplicate subscriptions in the same billing month
        $this->subscriptionService->assertCanSubscribe($company->id, $plan->id);
        $billingMonth = $this->subscriptionService->currentBillingMonth();

        return DB::transaction(function () use ($company, $plan, $providerEnum, $methodEnum, $billingMonth, $options) {
            $subscription = Subscription::create([
                'company_id'    => $company->id,
                'plan_id'       => $plan->id,
                'billing_month' => $billingMonth,
                'status'        => 'pending',
                'starts_at'     => now(),
                'ends_at'       => $plan->billing_cycle === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth(),
            ]);

            $payment = $this->paymentService->createPayment([
                'company_id'       => $company->id,
                'subscription_id'  => $subscription->id,
                'provider'         => $providerEnum->value,
                'method'           => $methodEnum->value,
                'amount'           => $plan->price,
                'currency'         => 'PHP',
                'billing_month'    => $billingMonth,
                'status'           => PaymentStatus::PENDING->value,
            ]);

            $gateway = $this->gatewayManager->resolve($providerEnum, $methodEnum);
            $checkout = $gateway->createCheckout($payment, $options);

            $payment->update([
                'provider_reference_id'       => $checkout->referenceId,
                'paymongo_checkout_id'        => $checkout->referenceId,
                'paymongo_payment_intent_id'  => $checkout->paymentIntentId,
                'checkout_url'                => $checkout->checkoutUrl,
                'metadata'                    => $checkout->metadata,
            ]);

            Log::info('Payment initiated', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'billing_month' => $billingMonth,
            ]);

            return [
                'subscription' => $subscription,
                'payment'      => $payment,
                'checkout_url' => $checkout->checkoutUrl,
            ];
        });
    }
}
