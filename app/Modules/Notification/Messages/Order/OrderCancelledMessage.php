<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderCancelledMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.cancelled';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->customerNumber(), $this->admin(), $this->driverNumber()];
    }

    public function body(): string
    {
        return sprintf(
            "Order %s has been cancelled.\nReason: %s",
            $this->order->order_no,
            $this->order->cancel_reason ?? 'not given',
        );
    }
}
