<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

use App\Models\User;

/**
 * Who has to see a newly placed order before it stops ringing.
 *
 * An order is written by one role *for* another to act on, and the buzzer
 * follows that: the admin writing a job rings the driver who must drive it,
 * and a driver writing one off the street rings the office that has to know
 * about it. Nobody is ever rung about an order they wrote themselves.
 */
enum OrderAlertEnum: string
{
    case Driver = 'driver';
    case Admin = 'admin';

    /**
     * Who to ring for an order this user just placed, or null for nobody.
     *
     * A **customer's** own order rings no one: the owner named the admin and
     * driver cases only, and a customer order already reaches the office on
     * WhatsApp. The nightly generator passes no user at all and so is also
     * silent — nobody is holding a phone at 00:05, and a driver woken by
     * three reminders from a routine would switch the whole thing off.
     */
    public static function forPlacer(?User $placedBy): ?self
    {
        if ($placedBy === null) {
            return null;
        }

        return match (true) {
            $placedBy->hasRole(RoleEnum::Admin->value) => self::Driver,
            $placedBy->hasRole(RoleEnum::Driver->value) => self::Admin,
            default => null,
        };
    }

    /**
     * Whether this user is the one being rung — the single check behind both
     * the alert list and marking an order seen.
     */
    public function isAudience(User $user): bool
    {
        return match ($this) {
            self::Admin => $user->hasRole(RoleEnum::Admin->value),
            self::Driver => $user->hasRole(RoleEnum::Driver->value),
        };
    }
}
