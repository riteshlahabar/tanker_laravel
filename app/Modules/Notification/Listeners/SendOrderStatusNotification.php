<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Services\OrderMessageFactory;
use App\Modules\Order\Events\OrderStatusChanged;

/**
 * The only link between orders and WhatsApp. The order service raises an
 * event; this decides whether anyone needs telling.
 */
final class SendOrderStatusNotification
{
    public function __construct(
        private readonly OrderMessageFactory $messages,
        private readonly WhatsAppDispatcherInterface $dispatcher,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $message = $this->messages->for($event->order, $event->to);

        if ($message !== null) {
            $this->dispatcher->dispatch($message);
        }
    }
}
