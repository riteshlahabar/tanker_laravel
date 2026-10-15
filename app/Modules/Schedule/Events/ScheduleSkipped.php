<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Events;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\ScheduleSkip;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer (or the admin) has called off one date of a routine.
 *
 * Raised rather than calling the notifier directly, so the Schedule module
 * knows nothing about WhatsApp and the Notification module subscribes without
 * either depending on the other.
 */
final class ScheduleSkipped
{
    use Dispatchable;

    public function __construct(
        public readonly ScheduleSkip $skip,
        /** The order that was cancelled, when one had already been generated. */
        public readonly ?Order $order,
        public readonly User $actor,
    ) {}
}
