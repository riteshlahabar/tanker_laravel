<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Services;

use App\Models\User;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPricing;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Support\GenerationReport;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Turns due schedules into ordinary orders.
 *
 * Everything goes through OrderService::place() — the same path the app uses —
 * so a generated order gets its order_no, its opening status log and its
 * notification, and from that moment nothing downstream can tell a standing
 * order from one the customer placed.
 */
final class ScheduledOrderGenerator implements ScheduledOrderGeneratorInterface
{
    /** Stops a nonsense pattern from walking forever. */
    private const MAX_DATES_PER_SCHEDULE = 60;

    public function __construct(
        private readonly DeliveryScheduleRepositoryInterface $schedules,
        private readonly ScheduleSkipRepositoryInterface $skips,
        private readonly OrderServiceInterface $orders,
        private readonly OrderPricing $pricing,
        private readonly GeneratorActor $actors,
    ) {}

    public function run(Carbon $target, bool $dryRun = false): GenerationReport
    {
        $report = new GenerationReport($dryRun);

        foreach ($this->schedules->dueBy($target) as $schedule) {
            $this->walk($schedule, $target, $report, $dryRun);
        }

        return $report;
    }

    public function generateOne(
        DeliverySchedule $schedule,
        Carbon $date,
        User $actor,
        bool $advanceCursor = false,
    ): ?Order {
        if (! $schedule->pattern()->matches($date)) {
            return null;
        }

        if ($this->orderFor($schedule, $date) !== null) {
            return null;
        }

        $order = $this->place($schedule, $date, $actor);

        if ($advanceCursor) {
            $this->setCursor($schedule, $schedule->pattern()->next($date));
        }

        return $order;
    }

    /**
     * Walks a schedule's due dates from its cursor up to the target.
     *
     * Walking rather than taking a single date is what lets a missed night
     * catch up: if last night's run never happened, tonight's creates both
     * dates instead of losing one.
     */
    private function walk(
        DeliverySchedule $schedule,
        Carbon $target,
        GenerationReport $report,
        bool $dryRun,
    ): void {
        $customer = $schedule->customer?->full_name ?? 'unknown customer';
        $cursor = $schedule->next_run_date === null
            ? null
            : Carbon::parse($schedule->next_run_date)->startOfDay();
        $seen = 0;

        while ($cursor !== null && $cursor->lte($target) && $seen++ < self::MAX_DATES_PER_SCHEDULE) {
            $handled = $this->handleDate($schedule, $cursor, $customer, $report, $dryRun);

            // The cursor moves only when the date is genuinely dealt with. A
            // date that failed is left in place so the next run retries it
            // rather than quietly losing a delivery.
            if (! $handled) {
                return;
            }

            $next = $schedule->pattern()->next($cursor);

            if (! $dryRun) {
                $this->setCursor($schedule, $next);
            }

            $cursor = $next;
        }
    }

    /**
     * @return bool whether the date was dealt with, and so whether the cursor
     *              may move past it
     */
    private function handleDate(
        DeliverySchedule $schedule,
        Carbon $date,
        string $customer,
        GenerationReport $report,
        bool $dryRun,
    ): bool {
        $day = $date->toDateString();

        if ($schedule->hasEnded($date)) {
            $report->skipped($schedule->id, $customer, $day, 'schedule has ended');

            if (! $dryRun) {
                $this->setCursor($schedule, null);
            }

            return false;
        }

        // The customer called this one day off. The cursor still moves past the
        // date — a skip changes this delivery, not the rhythm.
        if ($this->skips->exists($schedule, $date)) {
            $report->skipped($schedule->id, $customer, $day, 'customer called it off');

            return true;
        }

        if ($schedule->isPausedOn($date)) {
            $report->skipped($schedule->id, $customer, $day, 'paused');

            return true;
        }

        if ($schedule->customer === null || ! $schedule->customer->status) {
            $report->skipped($schedule->id, $customer, $day, 'customer is deactivated');

            return true;
        }

        if ($this->orderFor($schedule, $date) !== null) {
            $report->skipped($schedule->id, $customer, $day, 'order already exists');

            return true;
        }

        // Delivering yesterday means nothing, so a date left behind by a long
        // outage is rolled past rather than created.
        if ($date->lt(Carbon::today())) {
            $report->skipped($schedule->id, $customer, $day, 'date has passed');

            return true;
        }

        if ($dryRun) {
            $report->created($schedule->id, $customer, $day, '');

            return true;
        }

        try {
            $order = $this->place($schedule, $date, $this->actors->for($schedule));
            $report->created($schedule->id, $customer, $day, $order->order_no);

            return true;
        } catch (Throwable $e) {
            $report->failed($schedule->id, $customer, $day, $e->getMessage());

            return false;
        }
    }

    private function place(DeliverySchedule $schedule, Carbon $date, User $actor): Order
    {
        $customer = $schedule->customer;

        // delivery_at is resolved inside OrderService::place(), which also
        // strips delivery_time — so this just describes the delivery.
        $data = [
            'customer_id' => $schedule->customer_id,
            'delivery_schedule_id' => $schedule->id,
            'delivery_address' => $schedule->delivery_address ?: $customer?->address,
            'latitude' => $schedule->latitude ?? $customer?->latitude,
            'longitude' => $schedule->longitude ?? $customer?->longitude,
            'delivery_date' => $date->toDateString(),
            'delivery_slot_id' => $schedule->delivery_slot_id,
            'delivery_time' => $schedule->delivery_time,
            'quantity_litres' => $schedule->quantity_litres,
            // A schedule may fix its own price; otherwise the usual rule applies.
            'order_amount' => $schedule->order_amount !== null
                ? (float) $schedule->order_amount
                : $this->pricing->amountFor($schedule->quantity_litres),
            'notes' => $schedule->notes,
        ];

        $order = $this->orders->place($data, $actor);

        return $this->autoAssign($schedule, $order, $actor);
    }

    /**
     * A schedule naming its usual driver produces an order already assigned to
     * them and their current tanker; one without leaves it pending for the
     * admin. A driver who has since been deactivated or lost their tanker
     * leaves the order pending rather than failing the whole run.
     */
    private function autoAssign(DeliverySchedule $schedule, Order $order, User $actor): Order
    {
        if ($schedule->preferred_driver_id === null) {
            return $order;
        }

        try {
            return $this->orders->assign($order, $schedule->preferred_driver_id, null, $actor);
        } catch (OrderAssignmentException) {
            return $order;
        }
    }

    private function orderFor(DeliverySchedule $schedule, Carbon $date): ?Order
    {
        return Order::query()
            ->where('delivery_schedule_id', $schedule->id)
            ->whereDate('delivery_date', $date->toDateString())
            ->first();
    }

    private function setCursor(DeliverySchedule $schedule, ?Carbon $next): void
    {
        $schedule->forceFill(['next_run_date' => $next?->toDateString()])->save();
    }
}
