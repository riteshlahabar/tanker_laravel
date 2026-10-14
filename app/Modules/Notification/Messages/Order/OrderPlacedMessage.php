<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderPlacedMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.placed';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->admin(), $this->customerNumber()];
    }

    public function body(): string
    {
        return sprintf(
            "New water tanker order %s\nCustomer: %s\nDelivery: %s\nAddress: %s\nAmount: %s",
            $this->order->order_no,
            $this->order->customer?->full_name ?? '—',
            $this->deliveryLine(),
            $this->order->delivery_address,
            $this->money($this->order->order_amount),
        );
    }
}
