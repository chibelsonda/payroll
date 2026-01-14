<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve a payment gateway based on provider and method
     *
     * @param string $provider The payment provider (e.g., 'paymongo')
     * @param string $method The payment method (e.g., 'gcash', 'card')
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException If gateway not found
     */
    public function resolve(string $provider, string $method): PaymentGatewayInterface
    {
        $key = "{$provider}.{$method}";
        $gateways = Config::get('payments.gateways', []);

        if (!isset($gateways[$key])) {
            throw new InvalidArgumentException(
                "Payment gateway not found for provider '{$provider}' and method '{$method}'"
            );
        }

        $gatewayClass = $gateways[$key];

        if (!class_exists($gatewayClass)) {
            throw new InvalidArgumentException(
                "Payment gateway class '{$gatewayClass}' does not exist"
            );
        }

        $gateway = app($gatewayClass);

        if (!$gateway instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException(
                "Payment gateway class '{$gatewayClass}' must implement PaymentGatewayInterface"
            );
        }

        return $gateway;
    }
}
