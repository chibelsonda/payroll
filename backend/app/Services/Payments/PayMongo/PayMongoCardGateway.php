<?php

namespace App\Services\Payments\PayMongo;

class PayMongoCardGateway extends BasePayMongoGateway
{
    protected function getPaymentMethodTypes(): array
    {
        return ['card'];
    }
}
