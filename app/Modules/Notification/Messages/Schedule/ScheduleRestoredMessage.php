<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Schedule;

/**
 * A called-off date has been put back. The owner and the driver need telling
 * because the delivery is on again.
 */
final class ScheduleRestoredMessage extends ScheduleMessage
{
    public function template(): string
    {
        return 'schedule.restored';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return [$this->admin(), $this->driverNumber(), $this->customerNumber()];
    }

    public function body(): string
    {
        $lines = [
            sprintf('Delivery back on for %s.', $this->dateLabel()),
            sprintf('Customer: %s', $this->customerName()),
        ];

        $lines[] = $this->order !== null
            ? sprintf('Order %s has been created.', $this->order->order_no)
            : 'The order will be created by the nightly run.';

        return implode("\n", $lines);
    }
}
