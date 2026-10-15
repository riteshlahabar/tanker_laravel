<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Support\Carbon;

/**
 * Works out the one concrete moment a delivery is due, from the date plus
 * either a named slot or an exact time.
 *
 * This is resolved **once, at order creation**, and stored on
 * `orders.delivery_at` for two reasons: the six-hour skip check stays a single
 * comparison with no slot lookup, and editing a slot's hours later cannot
 * retroactively move orders already placed.
 *
 * It lives in the Order module rather than Schedule because the column it
 * fills belongs to orders, and both the app and the admin screen need it for
 * ordinary ad-hoc orders too — Order must not depend on Schedule.
 */
final class DeliveryAtResolver
{
    public function __construct(private readonly DeliverySlotRepositoryInterface $slots) {}

    /**
     * @param  array<string, mixed>  $data  an order's attributes
     * @return array<string, mixed> the same attributes with `delivery_at` and,
     *                              when a slot was named, its label copied onto
     *                              the free-text `delivery_slot` column
     */
    public function apply(array $data): array
    {
        // An input, not a column on orders: a schedule names an exact time
        // and this is where it is turned into delivery_at. Stripped here so
        // no caller has to remember to.
        $time = $data['delivery_time'] ?? null;
        unset($data['delivery_time']);

        $date = $data['delivery_date'] ?? null;

        if ($date === null) {
            return $data;
        }

        $day = Carbon::parse(is_string($date) ? $date : (string) $date)->startOfDay();
        $slot = $this->slotFor($data);

        if ($slot !== null) {
            return [
                ...$data,
                'delivery_slot_id' => $slot->id,
                // Keep the readable label too: the timeline and the WhatsApp
                // messages show it, and older orders only have this column.
                'delivery_slot' => $data['delivery_slot'] ?? $slot->name,
                'delivery_at' => $slot->startsOn($day->toDateString()),
            ];
        }

        if (is_string($time) && $time !== '') {
            return [
                ...$data,
                'delivery_slot' => $data['delivery_slot'] ?? Carbon::parse($time)->format('g:i A'),
                'delivery_at' => $day->copy()->setTimeFromTimeString($time),
            ];
        }

        // Neither given: anchor on the start of the day so `delivery_at` is
        // never null and the skip comparison always has something to read.
        return [...$data, 'delivery_at' => $day];
    }

    private function slotFor(array $data): ?DeliverySlot
    {
        $slotId = $data['delivery_slot_id'] ?? null;

        if ($slotId === null || $slotId === '') {
            return null;
        }

        return $this->slots->findOrNull((int) $slotId);
    }
}
