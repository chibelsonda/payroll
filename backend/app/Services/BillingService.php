<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\PaymentInitiationService;
use App\Services\Billing\SubscriptionService;
use App\Services\Payments\PaymentGatewayManager;
use App\Enums\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BillingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentService $paymentService,
        protected PaymentInitiationService $paymentInitiationService,
        protected SubscriptionService $subscriptionService,
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
    public function subscribe(
        Company $company,
        Plan $plan,
        string $provider,
        string $method,
        array $options = []
    ): array {
        return $this->paymentInitiationService->initiate(
            $company,
            $plan,
            $provider,
            $method,
            $options
        );
    }

    /**
     * Process a webhook from a payment provider
     */
    public function processWebhook(string $provider, $request): array
    {
        return DB::transaction(function () use ($provider, $request) {

            $providerEnum = PaymentProvider::tryFrom($provider);

            if (! $providerEnum) {
                throw new InvalidArgumentException('Invalid payment provider');
            }

            $gateway = $this->gatewayManager->resolve(
                $providerEnum,
                PaymentMethod::WEBHOOK
            );

            $result = $gateway->verifyWebhook($request);

            $payment = Payment::where('provider', $providerEnum->value)
                ->where(function ($q) use ($result) {
                    $q->where('provider_reference_id', $result->referenceId)
                    ->orWhere('paymongo_checkout_id', $result->referenceId)
                    ->orWhere('paymongo_payment_intent_id', $result->referenceId);
                })
                ->first();

            if (! $payment) {
                Log::warning('Payment not found for webhook', [
                    'reference' => $result->referenceId,
                ]);
                return ['payment' => null, 'subscription' => null];
            }

            if ($payment->status === 'paid') {
                return [
                    'payment' => $payment,
                    'subscription' => $payment->subscription,
                ];
            }

            $payment->update([
                'status' => $result->status,
                'paid_at' => $result->paidAt,
            ]);

            if ($result->isPaid() && $payment->subscription) {
                $payment->subscription->update([
                    'status' => 'active',
                ]);
            }

            return [
                'payment' => $payment,
                'subscription' => $payment->subscription,
            ];
        });
    }

    /**
     * Get company's payment history
     */
    public function getCompanyPayments(Company $company, int $perPage = 15)
    {
        return Payment::where('company_id', $company->id)
            ->with(['subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get current subscription for the billing month
     */
    public function currentSubscription(Company $company): ?Subscription
    {
        return $this->subscriptionService->getCurrentSubscription($company->id);
    }

    /**
     * Find payment by any known PayMongo reference (checkout or intent)
     */
    public function findPaymentByReference(string $reference): ?\App\Models\Payment
    {
        return Payment::query()
            ->where(function ($q) use ($reference) {
                $q->where('provider_reference_id', $reference)
                    ->orWhere('paymongo_checkout_id', $reference)
                    ->orWhere('paymongo_payment_intent_id', $reference);
            })
            ->first();
    }

    /**
     * Build a readonly payment status payload
     */
    public function buildPaymentStatusPayload(Payment $payment): array
    {
        $subscription = $payment->subscription;
        $plan = $subscription?->plan;

        return [
            'status' => $payment->status,
            'subscription_status' => $subscription?->status ?? 'inactive',
            'amount' => $payment->amount,
            'plan_name' => $plan?->name,
        ];
    }

       /**
     * Cancel a payment (PayMongo or other providers)
     */
    public function cancelPayment(Company $company, string $paymentIntentId, $user = null): Payment
    {
        $payment = Payment::where('paymongo_payment_intent_id', $paymentIntentId)
            ->orWhere('provider_reference_id', $paymentIntentId)
            ->first();

        if (! $payment) {
            throw new InvalidArgumentException('Payment not found for the provided reference.');
        }

        if ($payment->company_id !== $company->id) {
            throw new InvalidArgumentException('You are not authorized to cancel this payment.');
        }

        if ($payment->status === PaymentStatus::PAID->value) {
            throw new DomainException('Paid payments cannot be cancelled.');
        }

        if ($payment->status === PaymentStatus::CANCELLED->value) {
            return $payment;
        }

        $payment->status = PaymentStatus::CANCELLED->value;
        $payment->cancelled_at = now();
        $payment->save();

        Log::info('Payment cancelled', [
            'payment_id' => $payment->id,
            'company_id' => $payment->company_id,
            'user_id' => $user?->id,
        ]);

        return $payment;
    }
}
