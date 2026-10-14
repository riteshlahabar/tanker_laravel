<?php

declare(strict_types=1);

namespace App\Modules\Order\Contracts;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;

interface OrderServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function place(array $data, User $placedBy): Order;

    /**
     * Give the order to a driver, with the driver's current tanker and the
     * refill spot it will be filled at.
     *
     * @throws OrderAssignmentException
     * @throws InvalidStatusTransitionException
     */
    public function assign(Order $order, int $driverId, ?int $spotId, User $actor): Order;

    /**
     * @throws InvalidStatusTransitionException
     */
    public function moveTo(Order $order, OrderStatusEnum $status, User $actor, ?string $note = null): Order;

    public function cancel(Order $order, string $reason, User $actor): Order;
}
