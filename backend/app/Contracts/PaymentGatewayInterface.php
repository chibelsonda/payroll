<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Services\Payments\DTOs\PaymentGatewayCheckoutResponse;
use App\Services\Payments\DTOs\PaymentGatewayWebhookResult;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Create a checkout session for a payment
     *
     * @param Payment $payment The payment model
     * @param array $options Additional options (e.g., success_url, cancel_url)
     * @return PaymentGatewayCheckoutResponse
     */
    public function createCheckout(Payment $payment, array $options = []): PaymentGatewayCheckoutResponse;

    /**
     * Verify and process a webhook request
     *
     * @param Request $request The webhook request
     * @return PaymentGatewayWebhookResult
     */
    public function verifyWebhook(Request $request): PaymentGatewayWebhookResult;
}
