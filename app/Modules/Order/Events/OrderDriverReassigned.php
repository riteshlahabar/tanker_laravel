<?php

declare(strict_types=1);

namespace App\Modules\Order\Events;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The order has changed hands: the driver it was assigned to is not the one
 * who will deliver it. Raised in place of a status change, because the status
 * genuinely has not moved — the order is still `assigned`, just to somebody
 * else.
 *
 * Carries the previous driver because the order no longer knows about them
 * once the swap is written, and they are the person most needing to be told.
 */
final class OrderDriverReassigned
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly ?User $previousDriver,
        public readonly User $actor,
        /** Why the handover happened — "driver absent", typically. */
        public readonly ?string $reason = null,
    ) {}
}
