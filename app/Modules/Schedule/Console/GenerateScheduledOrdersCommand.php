<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Console;

use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Turns due standing orders into real orders.
 *
 * Scheduled 00:05 daily and generating **tomorrow's** orders, so the admin can
 * assign drivers over morning tea rather than scrambling on the day.
 */
final class GenerateScheduledOrdersCommand extends Command
{
    protected $signature = 'tanker:generate-scheduled-orders
        {--date= : Generate up to this date instead of tomorrow (Y-m-d)}
        {--days-ahead=1 : How far ahead of today to generate}
        {--dry-run : Show what would be created without writing anything}';

    protected $description = 'Create orders for standing-order customers that fall due';

    public function handle(ScheduledOrderGeneratorInterface $generator): int
    {
        $target = $this->target();
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            '%s scheduled orders up to %s.',
            $dryRun ? 'Previewing' : 'Generating',
            $target->format('D d M Y'),
        ));

        $report = $generator->run($target, $dryRun);

        if ($report->lines() !== []) {
            $this->table(
                ['Schedule', 'Customer', 'Delivery date', 'Outcome'],
                $report->lines(),
            );
        }

        $this->components->info($report->summary());

        // Non-zero on a failure so a cron wrapper can notice and shout.
        return $report->failedCount() > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function target(): Carbon
    {
        $date = $this->option('date');

        if (is_string($date) && $date !== '') {
            return Carbon::parse($date)->startOfDay();
        }

        return Carbon::today()->addDays(max(0, (int) $this->option('days-ahead')));
    }
}
