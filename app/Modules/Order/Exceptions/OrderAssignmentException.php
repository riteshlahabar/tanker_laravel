<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Core\Enums\OrderStatusEnum;
use RuntimeException;

final class OrderAssignmentException extends RuntimeException
{
    public static function driverHasNoTanker(): self
    {
        return new self('This driver has no tanker assigned. Give them a tanker first.');
    }

    /**
     * A handover is only open while the order is still sitting with its driver.
     * Once the tanker is being filled the water has been bought against that
     * driver, so the delivery — and its earning — stays with them.
     */
    public static function notReassignable(OrderStatusEnum $status): self
    {
        return new self(sprintf(
            'Only an order that is still waiting with its driver can be handed over. This one is %s.',
            $status->label(),
        ));
    }

    public static function notYetAssigned(): self
    {
        return new self('This order has no driver yet. Use Assign instead.');
    }

    public static function alreadyThatDriver(): self
    {
        return new self('That driver already has this order. Choose a different driver.');
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
