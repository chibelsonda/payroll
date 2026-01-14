<?php

namespace App\Services\Payments\PayMongo;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Services\Payments\DTOs\PaymentGatewayCheckoutResponse;
use App\Services\Payments\DTOs\PaymentGatewayWebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

abstract class BasePayMongoGateway implements PaymentGatewayInterface
{
    protected string $apiUrl;
    protected string $secretKey;
    protected string $webhookSecret;

    public function __construct()
    {
        $config = config('payments.paymongo');
        $this->apiUrl = $config['api_url'] ?? 'https://api.paymongo.com/v1';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->webhookSecret = $config['webhook_secret'] ?? '';

        if (empty($this->secretKey)) {
            throw new InvalidArgumentException('PayMongo secret key is not configured');
        }
    }

    /**
     * Get the payment method types for this gateway
     * Must be implemented by child classes
     */
    abstract protected function getPaymentMethodTypes(): array;

    /**
     * Create a checkout session
     */
    public function createCheckout(Payment $payment, array $options = []): PaymentGatewayCheckoutResponse
    {
        $successUrl = $options['success_url'] ?? config('app.frontend_url') . '/billing/success';
        $cancelUrl = $options['cancel_url'] ?? config('app.frontend_url') . '/billing/cancel';

        $amountInCents = (int) ($payment->amount * 100);

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->apiUrl}/checkout_sessions", [
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => true,
                        'show_description' => true,
                        'show_line_items' => true,
                        'line_items' => [
                            [
                                'currency' => $payment->currency,
                                'amount' => $amountInCents,
                                'name' => "Subscription Payment - {$payment->subscription->plan->name}",
                                'quantity' => 1,
                            ],
                        ],
                        'payment_method_types' => $this->getPaymentMethodTypes(),
                        'success_url' => $successUrl,
                        'cancel_url' => $cancelUrl,
                        'description' => "Subscription payment for {$payment->subscription->plan->name}",
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('PayMongo checkout creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payment_id' => $payment->id,
            ]);

            throw new \RuntimeException('Failed to create PayMongo checkout session');
        }

        $data = $response->json('data');
        $checkoutUrl = $data['attributes']['checkout_url'] ?? null;
        $referenceId = $data['id'] ?? null;

        if (!$checkoutUrl || !$referenceId) {
            throw new \RuntimeException('Invalid response from PayMongo checkout creation');
        }

        return new PaymentGatewayCheckoutResponse(
            checkoutUrl: $checkoutUrl,
            referenceId: $referenceId,
            metadata: $data['attributes'] ?? []
        );
    }

    /**
     * Verify webhook signature and extract payment data
     */
    public function verifyWebhook(Request $request): PaymentGatewayWebhookResult
    {
        $signature = $request->header('paymongo-signature');
        
        if (!$signature) {
            throw new InvalidArgumentException('Missing PayMongo signature header');
        }

        // Verify webhook signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new InvalidArgumentException('Invalid PayMongo webhook signature');
        }

        $data = $request->json('data');
        
        if (!$data) {
            throw new InvalidArgumentException('Invalid webhook payload');
        }

        $attributes = $data['attributes'] ?? [];
        $type = $data['type'] ?? '';

        // Handle checkout.session.completed event
        if ($type === 'checkout.session.completed') {
            $paymentIntentId = $attributes['payment_intent_id'] ?? null;
            
            if ($paymentIntentId) {
                // Fetch payment intent details
                $paymentIntent = $this->fetchPaymentIntent($paymentIntentId);
                $status = $this->mapPaymentStatus($paymentIntent['attributes']['status'] ?? 'pending');
                $paidAt = isset($paymentIntent['attributes']['paid_at']) 
                    ? new \DateTime($paymentIntent['attributes']['paid_at'])
                    : null;

                return new PaymentGatewayWebhookResult(
                    status: $status,
                    referenceId: $paymentIntentId,
                    paidAt: $paidAt,
                    rawData: $data
                );
            }
        }

        // Handle payment_intent.succeeded event
        if ($type === 'payment_intent.succeeded') {
            $status = $this->mapPaymentStatus($attributes['status'] ?? 'paid');
            $paidAt = isset($attributes['paid_at']) 
                ? new \DateTime($attributes['paid_at'])
                : null;

            return new PaymentGatewayWebhookResult(
                status: $status,
                referenceId: $data['id'] ?? '',
                paidAt: $paidAt,
                rawData: $data
            );
        }

        // Default: pending
        return new PaymentGatewayWebhookResult(
            status: 'pending',
            referenceId: $data['id'] ?? '',
            paidAt: null,
            rawData: $data
        );
    }

    /**
     * Fetch payment intent details from PayMongo
     */
    protected function fetchPaymentIntent(string $paymentIntentId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get("{$this->apiUrl}/payment_intents/{$paymentIntentId}");

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to fetch payment intent from PayMongo');
        }

        return $response->json('data', []);
    }

    /**
     * Map PayMongo payment status to our status
     */
    protected function mapPaymentStatus(string $paymongoStatus): string
    {
        return match ($paymongoStatus) {
            'succeeded', 'paid' => 'paid',
            'awaiting_payment_method', 'processing' => 'pending',
            'failed', 'canceled' => 'failed',
            default => 'pending',
        };
    }
}
