<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderOutForDeliveryMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.out-for-delivery';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->customerNumber()];
    }

    public function body(): string
    {
        return sprintf(
            "Order %s is on its way.\nDriver: %s (%s)\nTanker: %s",
            $this->order->order_no,
            $this->order->driver?->full_name ?? '—',
            $this->order->driver?->mobile ?? '—',
            $this->order->tanker?->vehicle_number ?? '—',
        );
    }
}
