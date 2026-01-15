<?php

namespace App\Services\Payments\PayMongo;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Services\Payments\DTOs\PaymentGatewayCheckoutResponse;
use App\Services\Payments\DTOs\PaymentGatewayWebhookResult;
use Illuminate\Http\Client\Response;
use Carbon\Carbon;
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

        /** @var Response $response */
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
        $paymentIntentId = $data['attributes']['payment_intent']['id'] ?? null;

        if (!$checkoutUrl || !$referenceId) {
            throw new \RuntimeException('Invalid response from PayMongo checkout creation');
        }

        Log::info('PayMongo checkout created', [
            'payment_id' => $payment->id,
            'reference_id' => $referenceId,
            'checkout_url' => $checkoutUrl,
            'response' => $data,
            'payment_intent_id' => $paymentIntentId,
        ]);

        return new PaymentGatewayCheckoutResponse(
            checkoutUrl: $checkoutUrl,
            referenceId: $referenceId,
            paymentIntentId: $paymentIntentId,
            metadata: $data['attributes'] ?? []
        );
    }

    /**
     * Verify webhook signature and extract payment data
     */
    public function verifyWebhook(Request $request): PaymentGatewayWebhookResult
    {
        $signatureHeader = $request->header('Paymongo-Signature');

        if (! $signatureHeader) {
            throw new InvalidArgumentException('Missing PayMongo signature header');
        }

        // RAW payload ONLY
        $payload = $request->getContent();

        // Parse signature header
        $timestamp = null;
        $signature = null;

        foreach (explode(',', $signatureHeader) as $part) {
            $part = trim($part);
            if (! str_contains($part, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $part, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === 't' && $timestamp === null) {
                $timestamp = $value;
            }

            // PayMongo: te = test, li = live
            if (($key === 'te' || $key === 'li') && $signature === null && $value !== '') {
                $signature = $value;
            }
        }

        if ($timestamp === null || $signature === null) {
            throw new InvalidArgumentException(
                'Malformed PayMongo signature header: ' . $signatureHeader
            );
        }

        // Verify HMAC
        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Invalid PayMongo webhook signature');
        }

        // Decode AFTER verification
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid webhook payload');
        }

        $eventType = data_get($decoded, 'data.attributes.type');

        // Normalize reference ID
        $referenceId =
            data_get($decoded, 'data.attributes.data.id') // cs_*
            ?? data_get($decoded, 'data.attributes.data.payment_intent.id'); // pi_*

        if (! $referenceId) {
            throw new InvalidArgumentException('Unable to resolve PayMongo reference ID');
        }

        $status =
            str_contains($eventType, 'paid') ? 'paid' :
            (str_contains($eventType, 'failed') ? 'failed' : 'pending');

        // Extract paid_at from all known PayMongo locations
        $paidAtRaw =
            data_get($decoded, 'data.attributes.data.paid_at') ??
            data_get($decoded, 'data.attributes.data.payments.0.paid_at') ??
            data_get($decoded, 'data.attributes.data.payment_intent.attributes.paid_at') ??
            data_get($decoded, 'data.attributes.data.payment_intent.attributes.payments.0.paid_at') ??
            null;

        $paidAt = $this->parsePaidAt($paidAtRaw);

        // Fallback: use webhook timestamp if paid but no paid_at
        if (! $paidAt && str_contains($eventType, 'paid') && $timestamp) {
            $paidAt = Carbon::createFromTimestamp((int) $timestamp);
        }

        Log::info('PayMongo webhook verified', [
            'event' => $eventType,
            'reference' => $referenceId,
            'status' => $status,
        ]);

        return new PaymentGatewayWebhookResult(
            status: $status,
            referenceId: $referenceId,
            paidAt: $paidAt,
            rawData: $decoded['data']
        );
    }

    /**
     * Fetch payment intent details from PayMongo
     */
    protected function fetchPaymentIntent(string $paymentIntentId): array
    {
        /** @var Response $response */
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

    /**
     * Parse PayMongo paid_at which can be epoch seconds or ISO string
     */
    protected function parsePaidAt($value): ?\DateTimeInterface
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp((int) $value);
            }

            return new \DateTime($value);
        } catch (\Exception $e) {
            Log::warning('Failed to parse PayMongo paid_at', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
