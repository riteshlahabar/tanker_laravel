<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Messages\Schedule\ScheduleRestoredMessage;
use App\Modules\Notification\Services\AdminRecipient;
use App\Modules\Schedule\Events\ScheduleRestored;

final class SendScheduleRestoreNotification
{
    public function __construct(
        private readonly AdminRecipient $admin,
        private readonly WhatsAppDispatcherInterface $dispatcher,
    ) {}

    public function handle(ScheduleRestored $event): void
    {
        $event->schedule->loadMissing('customer:id,first_name,last_name,mobile');
        $event->order?->loadMissing('driver:id,first_name,last_name,mobile');

        $this->dispatcher->dispatch(new ScheduleRestoredMessage(
            schedule: $event->schedule,
            date: $event->date,
            order: $event->order,
            adminNumber: $this->admin->number(),
        ));
    }
}
