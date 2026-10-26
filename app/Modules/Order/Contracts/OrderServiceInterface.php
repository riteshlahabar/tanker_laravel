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
     * The admin order form: the driver is chosen while the order is written,
     * so the order is placed and assigned in one go and the amount comes from
     * the tanker that driver holds.
     *
     * `$spotId` is the filling point the load is drawn from, when the admin
     * already knows it; left null it is decided on the route, as the Assign
     * screen allows.
     *
     * `$tankerRate` is the rate shown on the form. It is normally the
     * vehicle's own, in which case nothing is overridden and a later handover
     * re-prices from whichever tanker actually goes out; a rate the admin
     * *changed* is the figure of record and fixes both columns.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws OrderAssignmentException
     */
    public function placeForDriver(
        array $data,
        int $driverId,
        ?int $spotId,
        ?float $tankerRate,
        User $actor,
    ): Order;

    /**
     * Give the order to a driver, with the driver's current tanker and the
     * refill spot it will be filled at.
     *
     * @throws OrderAssignmentException
     * @throws InvalidStatusTransitionException
     */
    public function assign(Order $order, int $driverId, ?int $spotId, User $actor): Order;

    /**
     * Hand an already-assigned order to a different driver — the absent-driver
     * case, and the only way a standing order's preferred driver can be
     * overridden for one day without cancelling the order.
     *
     * The status does not move: the order stays `assigned`, with the new
     * driver, their current tanker and their own per-delivery rate.
     *
     * @throws OrderAssignmentException
     */
    public function reassign(
        Order $order,
        int $driverId,
        ?int $spotId,
        User $actor,
        ?string $reason = null,
    ): Order;

    /**
     * @throws InvalidStatusTransitionException
     */
    public function moveTo(Order $order, OrderStatusEnum $status, User $actor, ?string $note = null): Order;

    public function cancel(Order $order, string $reason, User $actor): Order;
}
