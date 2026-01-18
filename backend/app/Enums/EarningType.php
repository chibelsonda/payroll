<?php

namespace App\Enums;

enum EarningType: string
{
    case ALLOWANCE = 'allowance';
    case OVERTIME = 'overtime';
    case BONUS = 'bonus';
}
