<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages;

use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use Illuminate\Support\Collection;

/**
 * The 11:59pm summary: how many tankers each driver delivered and what they
 * earned. Built from DriverEarningReport, so it can never disagree with the
 * report screen.
 */
final class DailyReportMessage implements WhatsAppMessageInterface
{
    /**
     * @param  Collection<int, array<int, string|int|float|null>>  $rows
     * @param  array<string, string>  $totals
     */
    public function __construct(
        private readonly ?string $adminNumber,
        private readonly string $dateLabel,
        private readonly Collection $rows,
        private readonly array $totals,
    ) {}

    public function template(): string
    {
        return 'report.daily';
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return $this->adminNumber === null ? [] : [$this->adminNumber];
    }

    public function body(): string
    {
        $lines = ["Daily report — {$this->dateLabel}", ''];

        if ($this->rows->isEmpty()) {
            $lines[] = 'No deliveries today.';
        }

        foreach ($this->rows as $row) {
            // Driver, _, _, delivered, _, _, earning
            $lines[] = sprintf(
                '%s: %d tanker%s, ₹%s',
                $row[0],
                (int) $row[3],
                (int) $row[3] === 1 ? '' : 's',
                number_format((float) $row[6], 2),
            );
        }

        $lines[] = '';
        $lines[] = 'Total delivered: '.($this->totals['Tankers delivered'] ?? '0');
        $lines[] = 'Total earning: '.($this->totals['Total earning'] ?? '₹ 0.00');
        $lines[] = 'Order value: '.($this->totals['Order value'] ?? '₹ 0.00');

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'date' => $this->dateLabel,
            'drivers' => $this->rows->map(fn (array $row): array => [
                'driver' => $row[0],
                'delivered' => (int) $row[3],
                'earning' => (float) $row[6],
            ])->all(),
            'totals' => $this->totals,
        ];
    }
}
