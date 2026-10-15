<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Events;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * A called-off date has been put back. The order is null when the date was
 * still in the future and nothing had been generated for it yet.
 */
final class ScheduleRestored
{
    use Dispatchable;

    public function __construct(
        public readonly DeliverySchedule $schedule,
        public readonly Carbon $date,
        public readonly ?Order $order,
        public readonly User $actor,
    ) {}
}
