<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Services\Payments\PayMongo\PayMongoWebhookGateway;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Resolve a payment gateway based on provider and method
     *
     * @param PaymentProvider $provider The payment provider (e.g., 'paymongo')
     * @param PaymentMethod $method The payment method (e.g., 'gcash', 'card')
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException If gateway not found
     */
    public function resolve(
        PaymentProvider $provider,
        PaymentMethod $method
    ): PaymentGatewayInterface
    {
        // Normalize input to enums
        $providerEnum = $provider instanceof PaymentProvider ? $provider : PaymentProvider::tryFrom($provider);
        $methodEnum = $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom($method);

        if (!$providerEnum || !$methodEnum) {
            throw new InvalidArgumentException('Invalid payment provider or method');
        }

        if ($provider === PaymentProvider::PAYMONGO && $method === PaymentMethod::WEBHOOK) {
            $gateway = app(PayMongoWebhookGateway::class);

            if (!$gateway instanceof PaymentGatewayInterface) {
                throw new InvalidArgumentException(
                    "PayMongo webhook gateway must implement PaymentGatewayInterface"
                );
            }

            return $gateway;
        }

        $key = "{$providerEnum->value}.{$methodEnum->value}";
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
