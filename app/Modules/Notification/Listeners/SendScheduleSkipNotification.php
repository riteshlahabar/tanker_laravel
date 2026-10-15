<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Messages\Schedule\ScheduleSkippedMessage;
use App\Modules\Notification\Services\AdminRecipient;
use App\Modules\Schedule\Events\ScheduleSkipped;
use Illuminate\Support\Carbon;

/**
 * The only link between standing orders and WhatsApp for a skip. The schedule
 * module raises the event; this decides who hears about it.
 */
final class SendScheduleSkipNotification
{
    public function __construct(
        private readonly AdminRecipient $admin,
        private readonly WhatsAppDispatcherInterface $dispatcher,
    ) {}

    public function handle(ScheduleSkipped $event): void
    {
        $schedule = $event->skip->schedule;

        if ($schedule === null) {
            return;
        }

        $schedule->loadMissing('customer:id,first_name,last_name,mobile');
        $event->order?->loadMissing('driver:id,first_name,last_name,mobile');

        $this->dispatcher->dispatch(new ScheduleSkippedMessage(
            schedule: $schedule,
            date: Carbon::parse($event->skip->skip_date),
            order: $event->order,
            adminNumber: $this->admin->number(),
            reason: $event->skip->reason,
        ));
    }
}
