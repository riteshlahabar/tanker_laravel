<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Contracts;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Support\GenerationReport;
use Illuminate\Support\Carbon;

interface ScheduledOrderGeneratorInterface
{
    /**
     * Turn every schedule due on or before $target into ordinary orders.
     * Run nightly for tomorrow, so the admin can assign drivers in the
     * morning rather than on the day.
     */
    public function run(Carbon $target, bool $dryRun = false): GenerationReport;

    /**
     * One schedule, one date — used by "Generate now" and by restoring a
     * skipped date, neither of which should touch `next_run_date`.
     */
    public function generateOne(
        DeliverySchedule $schedule,
        Carbon $date,
        User $actor,
        bool $advanceCursor = false,
    ): ?Order;
}
