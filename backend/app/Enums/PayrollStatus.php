<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case PROCESSED = 'processed';
    case PAID = 'paid';
}
