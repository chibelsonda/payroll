<?php

namespace App\Services\Payments\PayMongo;

class PayMongoGcashGateway extends BasePayMongoGateway
{
    protected function getPaymentMethodTypes(): array
    {
        return ['gcash'];
    }
}
