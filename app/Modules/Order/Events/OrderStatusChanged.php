<?php

declare(strict_types=1);

namespace App\Modules\Order\Events;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised after every committed status change. The Notification module listens
 * for this to send WhatsApp messages, so the order service stays unaware of
 * how anyone is told.
 */
final class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly ?OrderStatusEnum $from,
        public readonly OrderStatusEnum $to,
    ) {}
}
