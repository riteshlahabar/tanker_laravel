<?php

declare(strict_types=1);

namespace App\Modules\Order\Policies;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Order\Models\Order;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::OrderView->value);
    }

    /**
     * A driver sees only orders handed to them; a customer only their own.
     */
    public function view(User $user, Order $order): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return match (true) {
            $user->isDriver() => $order->driver_id === $user->id,
            $user->isCustomer() => $order->customer_id === $user->id,
            default => true,
        };
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::OrderCreate->value);
    }

    public function assign(User $user, Order $order): bool
    {
        return $user->can(PermissionEnum::OrderAssign->value);
    }

    /**
     * The driver moves the order along the road; the admin can override.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        if (! $user->can(PermissionEnum::OrderUpdateStatus->value)) {
            return false;
        }

        return $user->isDriver() ? $order->driver_id === $user->id : true;
    }

    public function cancel(User $user, Order $order): bool
    {
        if (! $user->can(PermissionEnum::OrderCancel->value) && ! $user->isAdmin()) {
            return false;
        }

        if ($user->isCustomer()) {
            // A customer may only call off an order nobody has started yet.
            return $order->customer_id === $user->id && $order->status->value === 'pending';
        }

        return $order->status->isOpen();
    }
}
