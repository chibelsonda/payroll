<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LEAVE = 'leave';
    case INCOMPLETE = 'incomplete';
    case NEEDS_REVIEW = 'needs_review';
}
