<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class OrderAssignmentException extends RuntimeException
{
    public static function driverHasNoTanker(): self
    {
        return new self('This driver has no tanker assigned. Give them a tanker first.');
    }

    public static function driverInactive(): self
    {
        return new self('This driver is deactivated and cannot take orders.');
    }

    public static function spotUnusable(): self
    {
        return new self('That refill spot is unavailable. Choose another spot.');
    }
}
