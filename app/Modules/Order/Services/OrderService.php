<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Services\BaseService;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Order\Contracts\OrderNumberGeneratorInterface;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Models\Spot;

final class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DriverRepositoryInterface $drivers,
        private readonly SpotRepositoryInterface $spots,
        private readonly OrderNumberGeneratorInterface $numbers,
        private readonly OrderStatusTransitioner $transitioner,
        private readonly DeliveryAtResolver $deliveryAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function place(array $data, User $placedBy): Order
    {
        // Resolved here rather than in each controller, so every creation
        // path — app, admin screen, nightly generator — lands a concrete
        // delivery_at and the six-hour skip check always has something to
        // compare against.
        $data = $this->deliveryAt->apply($data);

        return $this->transaction(function () use ($data, $placedBy): Order {
            $order = $this->orders->store([
                ...$data,
                'order_no' => 'pending',
                'status' => OrderStatusEnum::Pending,
                'created_by' => $placedBy->id,
            ]);

            // The reference is derived from the row id, so it is written once
            // the insert has given us one.
            $order->update(['order_no' => $this->numbers->forId($order->id)]);

            $this->transitioner->recordInitial($order, $placedBy);

            OrderStatusChanged::dispatch($order->refresh(), null, OrderStatusEnum::Pending);

            return $order;
        });
    }

    public function assign(Order $order, int $driverId, ?int $spotId, User $actor): Order
    {
        $driver = $this->drivers->findOrFail($driverId);

        if (! $driver->status) {
            throw OrderAssignmentException::driverInactive();
        }

        $tanker = $driver->currentTanker();

        if ($tanker === null) {
            throw OrderAssignmentException::driverHasNoTanker();
        }

        $spot = $this->resolveSpot($spotId);

        return $this->transaction(fn (): Order => $this->transitioner->apply(
            order: $order,
            to: OrderStatusEnum::Assigned,
            actor: $actor,
            note: 'Assigned to '.$driver->full_name.' on '.$tanker->label(),
            extra: [
                'driver_id' => $driver->id,
                'tanker_id' => $tanker->id,
                'spot_id' => $spot?->id,
                // Snapshots, so later rate or charge changes never rewrite history.
                'refill_charge' => (float) ($spot?->refill_charge ?? 0),
                'driver_earning' => (float) $driver->per_delivery_rate,
            ],
        ));
    }

    public function moveTo(Order $order, OrderStatusEnum $status, User $actor, ?string $note = null): Order
    {
        return $this->transaction(
            fn (): Order => $this->transitioner->apply($order, $status, $actor, $note),
        );
    }

    public function cancel(Order $order, string $reason, User $actor): Order
    {
        return $this->transaction(fn (): Order => $this->transitioner->apply(
            order: $order,
            to: OrderStatusEnum::Cancelled,
            actor: $actor,
            note: $reason,
            extra: ['cancel_reason' => $reason],
        ));
    }

    private function resolveSpot(?int $spotId): ?Spot
    {
        if ($spotId === null) {
            return null;
        }

        $spot = $this->spots->findOrFail($spotId);

        if (! $spot->is_available || ! $spot->status) {
            throw OrderAssignmentException::spotUnusable();
        }

        return $spot;
    }
}
