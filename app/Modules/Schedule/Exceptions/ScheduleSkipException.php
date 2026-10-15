<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Exceptions;

use RuntimeException;

/**
 * A skip or restore the rules refuse. The message is shown to the customer as
 * it is, so each one says plainly why.
 */
final class ScheduleSkipException extends RuntimeException
{
    public static function tooLate(int $hours): self
    {
        return new self(
            "Deliveries can only be called off at least {$hours} hours in advance. Please ring the office."
        );
    }

    /**
     * The status backstop: once the tanker is being filled the water has been
     * bought for this customer, so nobody — admin included — may skip.
     */
    public static function alreadyUnderway(): self
    {
        return new self(
            'This delivery is already under way, so it can no longer be called off.'
        );
    }

    public static function notDue(string $date): self
    {
        return new self("This schedule has no delivery due on {$date}.");
    }

    public static function alreadySkipped(string $date): self
    {
        return new self("The delivery on {$date} is already called off.");
    }

    public static function notSkipped(string $date): self
    {
        return new self("The delivery on {$date} was not called off.");
    }

    public static function inThePast(): self
    {
        return new self('That date has already passed.');
    }
}
