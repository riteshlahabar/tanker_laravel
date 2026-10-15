<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Schedule;

use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Support\Carbon;

/**
 * Shared ground for the standing-order notifications: every one of them knows
 * the schedule, the date in question, the order if one existed, and the
 * owner's number.
 */
abstract class ScheduleMessage implements WhatsAppMessageInterface
{
    public function __construct(
        protected readonly DeliverySchedule $schedule,
        protected readonly Carbon $date,
        protected readonly ?Order $order = null,
        protected readonly ?string $adminNumber = null,
        protected readonly ?string $reason = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'schedule_id' => $this->schedule->id,
            'customer' => $this->schedule->customer?->full_name,
            'pattern' => $this->schedule->patternLabel(),
            'date' => $this->date->toDateString(),
            'order_no' => $this->order?->order_no,
            'reason' => $this->reason,
        ];
    }

    protected function customerNumber(): string
    {
        return (string) $this->schedule->customer?->mobile;
    }

    /**
     * The driver who was going to make this delivery. Taken from the order
     * rather than the schedule's preferred driver, because a reassignment may
     * mean somebody else is carrying it today.
     */
    protected function driverNumber(): string
    {
        return (string) $this->order?->driver?->mobile;
    }

    protected function admin(): string
    {
        return (string) $this->adminNumber;
    }

    protected function dateLabel(): string
    {
        return $this->date->format('D, d M Y');
    }

    protected function customerName(): string
    {
        return $this->schedule->customer?->full_name ?? 'A customer';
    }
}
