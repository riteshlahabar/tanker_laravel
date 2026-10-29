<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Core\Enums\OrderAlertEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Contracts\OrderAlertsInterface;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class OrderAlerts implements OrderAlertsInterface
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function pendingFor(User $user): Builder
    {
        $audience = $user->hasRole(RoleEnum::Admin->value) ? OrderAlertEnum::Admin : OrderAlertEnum::Driver;

        // `visibleTo` is what keeps a driver to their own orders — the same
        // scope every other driver query goes through, rather than a second
        // `where driver_id` written here that could drift from it.
        return $this->orders->visibleTo($user)->alerting($audience);
    }

    public function markSeen(Order $order): Order
    {
        if ($order->alert_seen_at !== null) {
            return $order;
        }

        $order->update(['alert_seen_at' => now()]);

        return $order->refresh();
    }
}
