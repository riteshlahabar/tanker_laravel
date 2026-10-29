<?php

declare(strict_types=1);

namespace App\Modules\Order\Contracts;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;

/**
 * The buzzer's server side: what is still unseen, and marking it seen.
 *
 * Its own contract rather than more methods on `OrderServiceInterface`
 * because it is a separate question about orders — "what has not been looked
 * at" — read on a timer by every open app, and because phase 3 may answer it
 * over a socket instead without touching ordering at all.
 */
interface OrderAlertsInterface
{
    /**
     * Orders still ringing for this user: written for their role, still open,
     * not yet seen — and, for a driver, only their own.
     */
    public function pendingFor(User $user): Builder;

    /**
     * Stop the ringing. Idempotent: a second call from the app's Stop button
     * after the order was opened must not look like a failure, and the first
     * timestamp is the one kept because that is when it was actually seen.
     */
    public function markSeen(Order $order): Order;
}
