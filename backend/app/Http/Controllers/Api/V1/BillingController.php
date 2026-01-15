<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DomainException;

class BillingController extends BaseApiController
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Get all active plans
     */
    public function plans(Request $request): JsonResponse
    {
        $plans = $this->billingService->getActivePlans();

        return $this->successResponse(
            PlanResource::collection($plans),
            'Plans retrieved successfully'
        );
    }

    /**
     * Subscribe to a plan
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $company = $request->attributes->get('active_company');

        if (!$company) {
            return $this->errorResponse('Company context is required', [], [], 403);
        }

        $plan = $this->billingService->findPlanByUuid($request->input('plan_uuid'));

        if (!$plan) {
            return $this->notFoundResponse('Plan not found');
        }

        try {
            $provider = $validated['provider'] ?? 'paymongo';
            $method = $validated['payment_method'];

            $result = $this->billingService->subscribe(
                $company,
                $plan,
                $provider,
                $method,
                [
                    'success_url' => $request->input('success_url'),
                    'cancel_url' => $request->input('cancel_url'),
                ]
            );

            return $this->createdResponse([
                'subscription' => new SubscriptionResource($result['subscription']),
                'payment' => new PaymentResource($result['payment']),
                'checkout_url' => $result['checkout_url'],
            ], 'Subscription created successfully. Please complete payment.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create subscription: ' . $e->getMessage(),
                [],
                [],
                500
            );
        }
    }

    /**
     * Get current subscription
     */
    public function subscription(Request $request): JsonResponse
    {
        $company = $request->attributes->get('active_company');

        if (!$company) {
            return $this->errorResponse('Company context is required', [], [], 403);
        }

        $subscription = $this->billingService->getCompanySubscription($company);

        if (!$subscription) {
            return $this->notFoundResponse('No subscription found');
        }

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully'
        );
    }

    /**
     * Get payment history
     */
    public function payments(Request $request): JsonResponse
    {
        $company = $request->attributes->get('active_company');

        if (!$company) {
            return $this->errorResponse('Company context is required', [], [], 403);
        }

        $perPage = $request->input('per_page', 15);
        $payments = $this->billingService->getCompanyPayments($company, $perPage);

        return $this->successResponse(
            PaymentResource::collection($payments),
            'Payments retrieved successfully'
        );
    }

    /**
     * Cancel a payment (auth required)
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'payment_intent_id' => ['required_without:reference_id', 'string'],
            'reference_id' => ['required_without:payment_intent_id', 'string'],
        ]);

        $company = $request->attributes->get('active_company');

        if (! $company) {
            return $this->errorResponse('Company context is required', [], [], 403);
        }

        $reference = $request->input('payment_intent_id') ?? $request->input('reference_id');

        try {
            $payment = $this->billingService->cancelPayment(
                $company,
                $reference,
                $request->user()
            );

            return $this->successResponse(new PaymentResource($payment), 'Payment cancelled successfully');
        } catch (\InvalidArgumentException|DomainException $e) {
            return $this->errorResponse($e->getMessage(), [], [], 400);
        } catch (\Throwable $e) {
            Log::error('Payment cancellation failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to cancel payment', [], [], 500);
        }
    }

    /**
     * Public payment status lookup by reference_id (checkout id or intent id)
     */
    public function status(Request $request): JsonResponse
    {
        $reference = $request->query('reference_id');

        if (!$reference) {
            return $this->validationErrorResponse(['reference_id' => ['reference_id is required']]);
        }

        $payment = $this->billingService->findPaymentByReference($reference);

        if (!$payment) {
            return $this->notFoundResponse('Payment not found');
        }

        Log::info('Billing status polled', [
            'reference' => $reference,
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ]);

        return $this->successResponse(
            $this->billingService->buildPaymentStatusPayload($payment),
            'Payment status retrieved'
        );
    }
}
