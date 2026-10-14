<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use App\Modules\Notification\Messages\Order\OrderAssignedMessage;
use App\Modules\Notification\Messages\Order\OrderCancelledMessage;
use App\Modules\Notification\Messages\Order\OrderDeliveredMessage;
use App\Modules\Notification\Messages\Order\OrderOutForDeliveryMessage;
use App\Modules\Notification\Messages\Order\OrderPlacedMessage;
use App\Modules\Notification\Messages\Order\OrderRefillingMessage;
use App\Modules\Order\Models\Order;

/**
 * Maps an order status to the message that announces it. One place to look
 * when asking "who hears about what".
 */
final class OrderMessageFactory
{
    /**
     * @var array<string, class-string<WhatsAppMessageInterface>>
     */
    private const MESSAGES = [
        'pending' => OrderPlacedMessage::class,
        'assigned' => OrderAssignedMessage::class,
        'refilling' => OrderRefillingMessage::class,
        'out_for_delivery' => OrderOutForDeliveryMessage::class,
        'delivered' => OrderDeliveredMessage::class,
        'cancelled' => OrderCancelledMessage::class,
    ];

    public function __construct(private readonly AdminRecipient $admin) {}

    public function for(Order $order, OrderStatusEnum $status): ?WhatsAppMessageInterface
    {
        $class = self::MESSAGES[$status->value] ?? null;

        if ($class === null) {
            return null;
        }

        $order->loadMissing(['customer:id,first_name,last_name,mobile',
            'driver:id,first_name,last_name,mobile', 'tanker:id,vehicle_name,vehicle_number', 'spot:id,name']);

        return new $class($order, $this->admin->number());
    }
}
