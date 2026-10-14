<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use App\Modules\Core\Enums\OrderStatusEnum;
use RuntimeException;

final class InvalidStatusTransitionException extends RuntimeException
{
    public static function between(OrderStatusEnum $from, OrderStatusEnum $to): self
    {
        return new self(sprintf(
            'An order that is %s cannot be moved to %s.',
            $from->label(),
            $to->label(),
        ));
    }
}
