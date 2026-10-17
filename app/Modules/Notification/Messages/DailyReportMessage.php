<?php

declare(strict_types=1);

namespace App\Modules\Notification\Messages;

use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use Illuminate\Support\Collection;

/**
 * The 11:59pm summary: what each driver delivered today.
 *
 * No money per driver. Drivers are salaried, so the only figure that could go
 * here is a monthly salary, and printing that against a single day's
 * deliveries reads as what the driver earned today — which is why the
 * dashboard's equivalent tile was dropped too.
 *
 * Built from DriverSalaryReport, so it can never disagree with the report
 * screen. It reads that report's rows BY POSITION, which nothing enforces, so
 * the indexes are named in one place below and asserted in
 * tests/Feature/Notification/DailyReportTest.php — reorder a column there and
 * the test fails instead of the owner's WhatsApp going quietly wrong.
 */
final class DailyReportMessage implements WhatsAppMessageInterface
{
    /** Column positions in DriverSalaryReport::headings(). */
    private const NAME = 0;

    private const DELIVERED = 3;

    private const TANKERS = 4;

    private const EXPENSES = 8;

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
            $tankers = (int) $row[self::TANKERS];

            $lines[] = sprintf(
                '%s: %d %s, %d tanker%s',
                $row[self::NAME],
                (int) $row[self::DELIVERED],
                (int) $row[self::DELIVERED] === 1 ? 'delivery' : 'deliveries',
                $tankers,
                $tankers === 1 ? '' : 's',
            );
        }

        $lines[] = '';
        $lines[] = 'Total deliveries: '.($this->totals['Deliveries'] ?? '0');
        $lines[] = 'Tankers delivered: '.($this->totals['Tankers delivered'] ?? '0');
        $lines[] = 'Order value: '.($this->totals['Order value'] ?? '₹ 0.00');
        // One line a day rather than a message per petrol fill — the owner's
        // choice (2026-10-17).
        $lines[] = 'Expenses today: '.($this->totals['Expenses in the range'] ?? '₹ 0.00');

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
                'driver' => $row[self::NAME],
                'delivered' => (int) $row[self::DELIVERED],
                'tankers' => (int) $row[self::TANKERS],
                'expenses' => (float) ($row[self::EXPENSES] ?? 0),
            ])->all(),
            'totals' => $this->totals,
        ];
    }
}
