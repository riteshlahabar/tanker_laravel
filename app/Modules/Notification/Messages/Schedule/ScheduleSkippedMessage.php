<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages\Schedule;

/**
 * A routine delivery has been called off for one day. The owner and the driver
 * need to know so nobody fills a tanker for it; the customer gets the same
 * message as a confirmation that the request landed.
 */
final class ScheduleSkippedMessage extends ScheduleMessage
{
    public function template(): string
    {
        return 'schedule.skipped';
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
            sprintf('Delivery called off for %s.', $this->dateLabel()),
            sprintf('Customer: %s', $this->customerName()),
        ];

        if ($this->order !== null) {
            $lines[] = sprintf('Order %s has been cancelled.', $this->order->order_no);
        }

        if ($this->reason !== null && $this->reason !== '') {
            $lines[] = sprintf('Reason: %s', $this->reason);
        }

        // Says plainly that nothing else changes — the question both the owner
        // and the customer ask next.
        $lines[] = sprintf('The rest of the routine (%s) continues as normal.', $this->schedule->patternLabel());

        return implode("\n", $lines);
    }
}
