<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Order;

use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use App\Modules\Order\Models\Order;

/**
 * Shared ground for the order notifications: every one of them knows the
 * order, the owner's number, and how to describe the delivery.
 */
abstract class OrderMessage implements WhatsAppMessageInterface
{
    public function __construct(
        protected readonly Order $order,
        protected readonly ?string $adminNumber = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'order_no' => $this->order->order_no,
            'status' => $this->order->status->value,
            'customer' => $this->order->customer?->full_name,
            'driver' => $this->order->driver?->full_name,
            'tanker' => $this->order->tanker?->vehicle_number,
            'tanker_count' => $this->order->tanker_count,
            'spot' => $this->order->spot?->name,
            'amount' => (float) $this->order->order_amount,
            'delivery_date' => $this->order->delivery_date?->toDateString(),
        ];
    }

    protected function customerNumber(): string
    {
        return (string) $this->order->customer?->mobile;
    }

    protected function driverNumber(): string
    {
        return (string) $this->order->driver?->mobile;
    }

    protected function admin(): string
    {
        return (string) $this->adminNumber;
    }

    protected function money(mixed $amount): string
    {
        return '₹'.number_format((float) $amount, 2);
    }

    protected function deliveryLine(): string
    {
        return trim(sprintf(
            '%s%s',
            $this->order->delivery_date?->format('d M Y') ?? '',
            $this->order->delivery_slot ? ', '.$this->order->delivery_slot : '',
        ));
    }
}
