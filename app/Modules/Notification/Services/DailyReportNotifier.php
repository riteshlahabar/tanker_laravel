<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Messages\DailyReportMessage;
use App\Modules\Report\Reports\DriverEarningReport;
use App\Modules\Report\Support\ReportFilters;

/**
 * Builds and sends the owner's daily summary. Used by the scheduled command
 * and by the "send now" button, so both produce identical figures.
 */
final class DailyReportNotifier
{
    public function __construct(
        private readonly DriverEarningReport $report,
        private readonly WhatsAppDispatcherInterface $dispatcher,
        private readonly AdminRecipient $admin,
    ) {}

    /**
     * @return array{queued: int, recipient: string|null, body: string}
     */
    public function sendFor(string $date): array
    {
        $filters = ReportFilters::forDay($date);

        $message = new DailyReportMessage(
            adminNumber: $this->admin->number(),
            dateLabel: $filters->label(),
            rows: $this->report->rows($filters)->filter(fn (array $row): bool => (int) $row[3] > 0)->values(),
            totals: $this->report->totals($filters),
        );

        return [
            'queued' => $this->dispatcher->dispatch($message),
            'recipient' => $this->admin->number(),
            'body' => $message->body(),
        ];
    }
}
