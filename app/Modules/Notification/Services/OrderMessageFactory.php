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
// Refilling notices are switched off — see the commented mapping below.
// use App\Modules\Notification\Messages\Order\OrderRefillingMessage;
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
        // Switched off at the owner's request: the customer is already told at
        // `assigned` and again at `out_for_delivery`, so the refill notice sat
        // between the two as noise. An unmapped status is a supported state —
        // `for()` returns null and the listener skips it — so nothing else
        // changes: `refilling` still transitions, stamps `refilled_at` and
        // writes its status log. To restore, uncomment this line and the
        // import above, and put 'order.refilling' back in
        // OrderNotificationTest::test_every_step_of_the_road_is_announced.
        // 'refilling' => OrderRefillingMessage::class,
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
