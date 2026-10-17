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
use App\Modules\Order\Events\OrderDriverReassigned;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;

final class OrderService extends BaseService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly DriverRepositoryInterface $drivers,
        private readonly SpotRepositoryInterface $spots,
        private readonly OrderNumberGeneratorInterface $numbers,
        private readonly OrderStatusTransitioner $transitioner,
        private readonly DeliveryAtResolver $deliveryAt,
        private readonly CustomerAddressResolver $customerAddress,
        private readonly OrderPricing $pricing,
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

        // Same reasoning: where to deliver defaults to the address the admin
        // entered for the customer, in one place rather than per form.
        $data = $this->customerAddress->apply($data);

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
                // Snapshot, so a later charge change never rewrites history.
                // The driver's pay is not snapshotted: a monthly salary is not
                // earned per order, so there is nothing per-order to record.
                'refill_charge' => (float) ($spot?->refill_charge ?? 0),
                ...$this->priceFor($order, $tanker),
            ],
        ));
    }

    /**
     * Water is priced per tanker-load and the owner's tankers are not all the
     * same, so the amount is only final once we know which vehicle goes out.
     *
     * Returns nothing for an order whose amount was *given* rather than
     * calculated — an admin typing it by hand, or a standing order carrying
     * its own figure. Re-pricing those would overwrite the owner's own number,
     * which is why `amount_fixed` exists.
     *
     * @return array<string, float>
     */
    private function priceFor(Order $order, Tanker $tanker): array
    {
        if ($order->amount_fixed) {
            return [];
        }

        return ['order_amount' => $this->pricing->amountFor($order->tanker_count, $tanker)];
    }

    public function reassign(
        Order $order,
        int $driverId,
        ?int $spotId,
        User $actor,
        ?string $reason = null,
    ): Order {
        if ($order->driver_id === null) {
            throw OrderAssignmentException::notYetAssigned();
        }

        // Deliberately narrower than "any open status": once the driver starts
        // refilling, the water has been bought against them, so the delivery
        // stays where it is.
        if ($order->status !== OrderStatusEnum::Assigned) {
            throw OrderAssignmentException::notReassignable($order->status);
        }

        $driver = $this->drivers->findOrFail($driverId);

        if ($driver->id === $order->driver_id) {
            throw OrderAssignmentException::alreadyThatDriver();
        }

        if (! $driver->status) {
            throw OrderAssignmentException::driverInactive();
        }

        $tanker = $driver->currentTanker();

        if ($tanker === null) {
            throw OrderAssignmentException::driverHasNoTanker();
        }

        $spot = $this->resolveSpot($spotId);

        // Read before the swap: afterwards the order no longer knows who had it.
        $previous = $order->driver;

        return $this->transaction(function () use ($order, $driver, $tanker, $spot, $previous, $actor, $reason): Order {
            $order->update([
                'driver_id' => $driver->id,
                'tanker_id' => $tanker->id,
                // The spot is only re-snapshotted when a new one is named;
                // keeping the original is right when only the driver changed.
                ...($spot === null ? [] : [
                    'spot_id' => $spot->id,
                    'refill_charge' => (float) $spot->refill_charge,
                ]),
                // A different driver brings a different tanker, which may be
                // priced differently.
                ...$this->priceFor($order, $tanker),
                // `assigned_at` is left alone on purpose: the order was assigned
                // when it first was, and re-stamping it would tell the reports
                // this order waited less than it did.
            ]);

            $this->transitioner->recordChange($order, $actor, $this->handoverNote($previous, $driver->full_name, $reason));

            OrderDriverReassigned::dispatch($order->refresh(), $previous, $actor, $reason);

            return $order;
        });
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

    private function handoverNote(?User $previous, string $to, ?string $reason): string
    {
        return trim(sprintf(
            'Driver changed from %s to %s%s',
            $previous?->full_name ?? 'an unnamed driver',
            $to,
            $reason === null || trim($reason) === '' ? '' : ' — '.trim($reason),
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
