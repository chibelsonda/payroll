<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends BaseApiController
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Handle PayMongo webhook
     */
    public function paymongo(Request $request): JsonResponse
    {
        try {
            // Determine payment method from webhook data
            $data = $request->json('data', []);
            $attributes = $data['attributes'] ?? [];
            
            // Try to determine method from payment method types or payment intent
            $method = $this->determinePaymentMethod($data, $attributes);
            
        if (!$method) {
            // Default to card if we can't determine
            $method = 'card';
        }

            $provider = 'paymongo';

            $result = $this->billingService->processWebhook($provider, $method, $request);

            return $this->successResponse([
                'payment' => new \App\Http\Resources\PaymentResource($result['payment']),
                'subscription' => $result['subscription'] 
                    ? new \App\Http\Resources\SubscriptionResource($result['subscription'])
                    : null,
            ], 'Webhook processed successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), [], [], 400);
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to process webhook: ' . $e->getMessage(),
                [],
                [],
                500
            );
        }
    }

    /**
     * Determine payment method from webhook data
     */
    protected function determinePaymentMethod(array $data, array $attributes): ?string
    {
        // Check payment method types in checkout session
        if (isset($attributes['payment_method_types'])) {
            $types = $attributes['payment_method_types'];
            if (in_array('gcash', $types)) {
                return 'gcash';
            }
            if (in_array('card', $types)) {
                return 'card';
            }
        }

        // Check payment intent payment method
        if (isset($attributes['payment_method'])) {
            $paymentMethod = $attributes['payment_method'];
            if (isset($paymentMethod['type'])) {
                $type = $paymentMethod['type'];
                if ($type === 'gcash') {
                    return 'gcash';
                }
                if ($type === 'card') {
                    return 'card';
                }
            }
        }

        return null;
    }
}
