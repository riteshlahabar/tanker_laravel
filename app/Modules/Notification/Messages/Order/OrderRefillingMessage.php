<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

final class OrderRefillingMessage extends OrderMessage
{
    public function template(): string
    {
        return 'order.refilling';
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
            'Order %s: your tanker is being filled at %s and will leave shortly.',
            $this->order->order_no,
            $this->order->spot?->name ?? 'the filling point',
        );
    }
}
