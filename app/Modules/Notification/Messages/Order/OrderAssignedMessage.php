<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderAssignedMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.assigned';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->customerNumber(), $this->driverNumber()];
    }

    public function body(): string
    {
        // The amount is included because assignment is where it can change:
        // water is priced per tanker-load and the rate belongs to the vehicle,
        // which is not known until a driver is given the order.
        return sprintf(
            "Order %s is assigned.\nDriver: %s (%s)\nTanker: %s\nTankers: %d\nAmount: %s\n".
            "Filling point: %s\nDelivery: %s\nAddress: %s",
            $this->order->order_no,
            $this->order->driver?->full_name ?? '—',
            $this->order->driver?->mobile ?? '—',
            $this->order->tanker?->label() ?? '—',
            $this->order->tanker_count ?? 1,
            $this->money($this->order->order_amount),
            $this->order->spot?->name ?? 'to be decided on the route',
            $this->deliveryLine(),
            $this->order->delivery_address,
        );
    }
}
