<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Mappings
    |--------------------------------------------------------------------------
    |
    | Maps provider + method combinations to their gateway implementation classes.
    | This allows the PaymentGatewayManager to resolve the correct gateway
    | without using switch/case or if/else statements.
    |
    | Format: 'provider.method' => GatewayClass::class
    |
    */

    'gateways' => [
        'paymongo.gcash' => \App\Services\Payments\PayMongo\PayMongoGcashGateway::class,
        'paymongo.card' => \App\Services\Payments\PayMongo\PayMongoCardGateway::class,
        // Future gateways can be added here without modifying existing code:
        // 'paymongo.maya' => \App\Services\Payments\PayMongo\PayMongoMayaGateway::class,
        // 'stripe.card' => \App\Services\Payments\Stripe\StripeCardGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | PayMongo Configuration
    |--------------------------------------------------------------------------
    */

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'api_url' => env('PAYMONGO_API_URL', 'https://api.paymongo.com/v1'),
    ],
];
