<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderDeliveredMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.delivered';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->customerNumber(), $this->admin()];
    }

    public function body(): string
    {
        return sprintf(
            "Order %s delivered.\nCustomer: %s\nDriver: %s\nAmount: %s\nThank you.",
            $this->order->order_no,
            $this->order->customer?->full_name ?? '—',
            $this->order->driver?->full_name ?? '—',
            $this->money($this->order->order_amount),
        );
    }
}
