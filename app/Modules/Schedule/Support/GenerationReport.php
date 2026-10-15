<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Support;

/**
 * What a generator run did. Mutable while the run is in progress, then read by
 * the command for its output and by the panel for its flash message — so a
 * dry run and a real run report in exactly the same shape.
 */
final class GenerationReport
{
    /**
     * @var array<int, array{schedule: int, customer: string, date: string, outcome: string}>
     */
    private array $lines = [];

    private int $created = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function __construct(public readonly bool $dryRun = false) {}

    public function created(int $scheduleId, string $customer, string $date, string $orderNo): void
    {
        $this->created++;
        $this->lines[] = [
            'schedule' => $scheduleId,
            'customer' => $customer,
            'date' => $date,
            'outcome' => $this->dryRun ? 'would create' : "created {$orderNo}",
        ];
    }

    public function skipped(int $scheduleId, string $customer, string $date, string $why): void
    {
        $this->skipped++;
        $this->lines[] = [
            'schedule' => $scheduleId,
            'customer' => $customer,
            'date' => $date,
            'outcome' => "skipped — {$why}",
        ];
    }

    public function failed(int $scheduleId, string $customer, string $date, string $why): void
    {
        $this->failed++;
        $this->lines[] = [
            'schedule' => $scheduleId,
            'customer' => $customer,
            'date' => $date,
            'outcome' => "failed — {$why}",
        ];
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function failedCount(): int
    {
        return $this->failed;
    }

    /**
     * @return array<int, array{schedule: int, customer: string, date: string, outcome: string}>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function summary(): string
    {
        return $this->dryRun
            ? "Would create {$this->created}; {$this->skipped} skipped."
            : "Created {$this->created}; {$this->skipped} skipped; {$this->failed} failed.";
    }
}
