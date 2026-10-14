<?php

declare(strict_types=1);

namespace App\Modules\Notification\Console;

use App\Modules\Notification\Services\DailyReportNotifier;
use Illuminate\Console\Command;

/**
 * Scheduled for 23:59 in routes/console.php. Also runnable by hand, and by the
 * "Send today's report now" button in the panel.
 */
final class SendDailyReportCommand extends Command
{
    protected $signature = 'tanker:daily-report
                            {--date= : The day to report on, defaults to today}
                            {--show : Print the message instead of only queueing it}';

    protected $description = "Send the owner the day's per-driver delivery and earning summary on WhatsApp";

    public function handle(DailyReportNotifier $notifier): int
    {
        $date = $this->option('date') ?: now()->toDateString();

        $result = $notifier->sendFor($date);

        if ($result['recipient'] === null) {
            $this->components->warn(
                'No admin WhatsApp number is set — add one in Settings. The report was not queued.',
            );

            return self::FAILURE;
        }

        if ($this->option('show')) {
            $this->newLine();
            $this->line($result['body']);
            $this->newLine();
        }

        $this->components->info(sprintf(
            'Daily report for %s queued to %s.',
            $date,
            $result['recipient'],
        ));

        return self::SUCCESS;
    }
}
