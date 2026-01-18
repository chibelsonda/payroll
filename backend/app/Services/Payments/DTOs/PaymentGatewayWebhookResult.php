<?php

namespace App\Services\Payments\DTOs;

class PaymentGatewayWebhookResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $referenceId,
        public readonly ?\DateTime $paidAt = null,
        public readonly array $rawData = []
    ) {}

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'reference_id' => $this->referenceId,
            'paid_at' => $this->paidAt?->format('Y-m-d H:i:s'),
            'raw_data' => $this->rawData,
        ];
    }
}
