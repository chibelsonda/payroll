<?php

namespace App\Services\Payments\PayMongo;

class PayMongoWebhookGateway extends BasePayMongoGateway
{
    /*
    * Get the payment method types for this gateway
    * Must be implemented by child classes
    */
    public function getPaymentMethodTypes(): array
    {
        return [];
    }
}
