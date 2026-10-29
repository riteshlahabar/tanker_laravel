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

    /**
     * Only the audience the buzzer is ringing may stop it.
     *
     * Not the same question as [view]: an admin may read every order, but an
     * order written *by* the admin is not theirs to mark seen — the driver it
     * was handed to is the one whose phone is ringing, and silencing it from
     * the office would lose the record of whether the job was ever looked at.
     */
    public function markSeen(User $user, Order $order): bool
    {
        if ($order->alert_for === null || ! $this->view($user, $order)) {
            return false;
        }

        return $order->alert_for->isAudience($user);
    }

    public function assign(User $user, Order $order): bool
    {
        return $user->can(PermissionEnum::OrderAssign->value);
    }

    /**
     * Handing the order to a different driver. Same permission as the first
     * assignment — whether the service will actually allow it depends on the
     * order's status, which is the service's rule, not the policy's.
     */
    public function reassign(User $user, Order $order): bool
    {
        return $this->assign($user, $order);
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
