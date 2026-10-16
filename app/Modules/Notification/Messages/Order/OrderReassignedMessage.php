<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

use App\Models\User;
use App\Modules\Order\Models\Order;

/**
 * The order has changed hands. Three people need to know and for different
 * reasons: the driver who has lost it (so they stop expecting it), the driver
 * who has gained it, and the customer (whose arriving driver is now somebody
 * else). The owner is told because they may not be the one who made the change.
 */
final class OrderReassignedMessage extends OrderMessage
{
    public function __construct(
        Order $order,
        ?string $adminNumber = null,
        private readonly ?User $previousDriver = null,
        private readonly ?string $reason = null,
    ) {
        parent::__construct($order, $adminNumber);
    }

    public function template(): string
    {
        return 'order.reassigned';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [
            $this->previousDriverNumber(),
            $this->driverNumber(),
            $this->customerNumber(),
            $this->admin(),
        ];
    }

    public function body(): string
    {
        return sprintf(
            "Order %s has a new driver.\nWas: %s\nNow: %s (%s)\nTanker: %s\nDelivery: %s\nAddress: %s%s",
            $this->order->order_no,
            $this->previousDriver?->full_name ?? '—',
            $this->order->driver?->full_name ?? '—',
            $this->order->driver?->mobile ?? '—',
            $this->order->tanker?->label() ?? '—',
            $this->deliveryLine(),
            $this->order->delivery_address,
            $this->reason === null || trim($this->reason) === '' ? '' : "\nReason: ".trim($this->reason),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            ...parent::payload(),
            'previous_driver' => $this->previousDriver?->full_name,
            'reason' => $this->reason,
        ];
    }

    private function previousDriverNumber(): string
    {
        return (string) $this->previousDriver?->mobile;
    }
}
