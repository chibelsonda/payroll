<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'provider' => $this->provider,
            'method' => $this->method,
            'provider_reference_id' => $this->provider_reference_id,
            'checkout_url' => $this->checkout_url,
            'paymongo_checkout_id' => $this->paymongo_checkout_id,
            'paymongo_payment_intent_id' => $this->paymongo_payment_intent_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
            'subscription' => $this->whenLoaded('subscription', fn() => new SubscriptionResource($this->subscription)),
            'subscription_uuid' => $this->subscription?->uuid,
            'company_uuid' => $this->company?->uuid,
            'is_paid' => $this->isPaid(),
            'is_pending' => $this->isPending(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
