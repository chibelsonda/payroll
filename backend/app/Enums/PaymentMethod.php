<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case GCASH   = 'gcash';
    case CARD    = 'card';

    // Internal-only (not a real payment method)
    case WEBHOOK = 'webhook';
}
