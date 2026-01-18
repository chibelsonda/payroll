<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Owner      = 'owner';
    case Admin      = 'admin';
    case HR         = 'hr';
    case Accountant = 'accountant';
    case Employee   = 'employee';

    /**
     * For Spatie assignRole() compatibility
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Optional helper: get all role values
     */
    public static function values(): array
    {
        return array_map(fn ($role) => $role->value, self::cases());
    }
}
