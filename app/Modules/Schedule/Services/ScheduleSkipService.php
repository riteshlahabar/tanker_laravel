<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Events\ScheduleRestored;
use App\Modules\Schedule\Events\ScheduleSkipped;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Models\ScheduleSkip;
use App\Modules\Schedule\Support\SkipWindow;
use App\Modules\Schedule\Support\UpcomingDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calling off one day of a routine, and putting it back.
 *
 * The skip is held against the schedule and the date rather than an order, so
 * one record covers both situations: the order already exists and is
 * cancelled, or nothing is generated yet and the generator must leave the date
 * alone. It survives regeneration, and it never moves `next_run_date` — the
 * routine carries on exactly as it was.
 */
final class ScheduleSkipService extends BaseService implements ScheduleSkipServiceInterface
{
    /** The cancellation reason written onto a skipped order. */
    public const CANCEL_REASON = 'Customer skipped this delivery';

    public function __construct(
        private readonly ScheduleSkipRepositoryInterface $skips,
        private readonly OrderServiceInterface $orders,
        private readonly ScheduledOrderGeneratorInterface $generator,
        private readonly SkipWindow $window,
    ) {}

    public function skip(
        DeliverySchedule $schedule,
        Carbon $date,
        User $actor,
        ?string $reason = null,
    ): ScheduleSkip {
        $day = $date->copy()->startOfDay();

        $this->assertSkippable($schedule, $day);

        // Any order for the date, open or not: the status backstop has to see a
        // delivered one to refuse it. Narrowing to open orders here would hand
        // the window a null and let a delivered date be "skipped".
        $order = $this->orderFor($schedule, $day);

        // Both rules live in SkipWindow, so the can_skip flag the app reads and
        // the refusal the server gives can never disagree.
        $this->window->guard(
            dueAt: $order?->delivery_at ?? $schedule->dueAt($day),
            order: $order,
            asAdmin: $actor->isAdmin(),
        );

        return $this->transaction(function () use ($schedule, $day, $actor, $reason, $order): ScheduleSkip {
            // Cancelled through the order service, so it gets a status-log row
            // and leaves the driver's list like any other cancellation. An
            // order already cancelled is left alone; the skip row is still
            // written so the generator does not recreate the date.
            if ($order !== null && $order->status->isOpen()) {
                $this->orders->cancel($order, self::CANCEL_REASON, $actor);
            }

            $skip = $this->skips->store([
                'delivery_schedule_id' => $schedule->id,
                'skip_date' => $day->toDateString(),
                'reason' => $reason,
                'requested_by' => $actor->id,
                'order_id' => $order?->id,
            ]);

            // Admin and the assigned driver are told by a listener, so this
            // service knows nothing about WhatsApp.
            ScheduleSkipped::dispatch($skip->load('schedule.customer'), $order, $actor);

            return $skip;
        });
    }

    public function restore(DeliverySchedule $schedule, Carbon $date, User $actor): void
    {
        $day = $date->copy()->startOfDay();
        $skip = $this->skips->find($schedule, $day);

        if ($skip === null) {
            throw ScheduleSkipException::notSkipped($day->toDateString());
        }

        if ($day->lt(Carbon::today())) {
            throw ScheduleSkipException::inThePast();
        }

        // A customer may undo only while their six-hour window is still open;
        // the admin may undo at any time.
        $this->window->guard(
            dueAt: $schedule->dueAt($day),
            order: null,
            asAdmin: $actor->isAdmin(),
        );

        $this->transaction(function () use ($schedule, $day, $actor, $skip): void {
            $this->skips->remove($skip);

            // Regenerate that one date. The cursor is deliberately untouched:
            // it has already moved past this date, and the rhythm is unchanged.
            $order = $this->generator->generateOne($schedule, $day, $actor);

            ScheduleRestored::dispatch($schedule->load('customer'), $day, $order, $actor);
        });
    }

    /**
     * @return Collection<int, UpcomingDate>
     */
    public function upcoming(DeliverySchedule $schedule, User $actor, int $count = 14): Collection
    {
        $dates = $schedule->pattern()->upcoming(Carbon::today(), $count);

        if ($dates === []) {
            return collect();
        }

        // Two queries for the whole list rather than two per date.
        $skips = $this->skips->betweenKeyedByDate($schedule, $dates[0], end($dates));
        $orders = $this->ordersKeyedByDate($schedule, $dates[0], end($dates));
        $isAdmin = $actor->isAdmin();

        return collect($dates)->map(function (Carbon $date) use ($schedule, $skips, $orders, $isAdmin): UpcomingDate {
            $key = $date->toDateString();
            $order = $orders->get($key);
            $skip = $skips->get($key);
            $dueAt = $order?->delivery_at ?? $schedule->dueAt($date);
            $refusal = $this->window->refusal($dueAt, $order, $isAdmin);

            return new UpcomingDate(
                date: $date,
                dueAt: $dueAt,
                order: $order,
                skipped: $skip !== null,
                // Already called off: the button becomes Restore, not Skip.
                canSkip: $skip === null && $refusal === null,
                skipReason: $refusal?->getMessage(),
                cancelledReason: $skip?->reason,
            );
        });
    }

    /**
     * @return Collection<string, Order>
     */
    private function ordersKeyedByDate(DeliverySchedule $schedule, Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->whereBetween('delivery_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (Order $order): string => Carbon::parse($order->delivery_date)->toDateString());
    }

    private function assertSkippable(DeliverySchedule $schedule, Carbon $day): void
    {
        if ($day->lt(Carbon::today())) {
            throw ScheduleSkipException::inThePast();
        }

        if (! $schedule->pattern()->matches($day)) {
            throw ScheduleSkipException::notDue($day->toDateString());
        }

        if ($this->skips->exists($schedule, $day)) {
            throw ScheduleSkipException::alreadySkipped($day->toDateString());
        }
    }

    /**
     * The order this schedule produced for that date, whatever state it is in.
     */
    private function orderFor(DeliverySchedule $schedule, Carbon $day): ?Order
    {
        return Order::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->whereDate('delivery_date', $day->toDateString())
            ->first();
    }
}
