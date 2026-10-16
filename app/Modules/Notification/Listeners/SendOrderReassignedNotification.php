<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Messages\Order\OrderReassignedMessage;
use App\Modules\Notification\Services\AdminRecipient;
use App\Modules\Order\Events\OrderDriverReassigned;

/**
 * A handover is not a status change, so it does not pass through
 * OrderMessageFactory (which maps a status to its message). It has its own
 * event and its own message for the same reason the Schedule skips do.
 */
final class SendOrderReassignedNotification
{
    public function __construct(
        private readonly AdminRecipient $admin,
        private readonly WhatsAppDispatcherInterface $dispatcher,
    ) {}

    public function handle(OrderDriverReassigned $event): void
    {
        $event->order->loadMissing([
            'customer:id,first_name,last_name,mobile',
            'driver:id,first_name,last_name,mobile',
            'tanker:id,vehicle_name,vehicle_number',
        ]);

        $this->dispatcher->dispatch(new OrderReassignedMessage(
            order: $event->order,
            adminNumber: $this->admin->number(),
            previousDriver: $event->previousDriver,
            reason: $event->reason,
        ));
    }
}
