<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $this->billingService->processWebhook('paymongo', $request);
            return $this->successResponse([], 'OK');
        } catch (\InvalidArgumentException $e) {
            Log::warning('PayMongo webhook bad request', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse($e->getMessage(), [], [], 400);

        } catch (\Throwable $e) {
            Log::error('PayMongo webhook failed', [
                'error' => $e->getMessage(),
            ]);

            // Still return 200 to stop retries
            return $this->successResponse([], 'OK');
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
