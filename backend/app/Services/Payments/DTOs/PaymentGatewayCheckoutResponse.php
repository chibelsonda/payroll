<?php

namespace App\Services\Payments\DTOs;

class PaymentGatewayCheckoutResponse
{
    public function __construct(
        public readonly string $checkoutUrl,
        public readonly string $referenceId,
        public readonly ?string $paymentIntentId = null,
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'checkout_url' => $this->checkoutUrl,
            'reference_id' => $this->referenceId,
            'payment_intent_id' => $this->paymentIntentId,
            'metadata' => $this->metadata,
        ];
    }
}
