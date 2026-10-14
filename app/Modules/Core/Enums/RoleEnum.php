<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Driver = 'driver';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin / Owner',
            self::Driver => 'Driver',
            self::Customer => 'Customer',
        };
    }

    /**
     * Roles that sign in with a mobile number instead of an email address.
     */
    public function signsInWithMobile(): bool
    {
        return $this !== self::Admin;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
