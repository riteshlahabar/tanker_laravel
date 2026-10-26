<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Resources;

use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliverySchedule
 */
final class DeliveryScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->full_name,
            'customer_mobile' => $this->customer?->mobile,

            // The rhythm in both forms: the numbers for an edit screen, the
            // words for a list row.
            'interval_days' => (int) $this->interval_days,
            'weekdays' => $this->weekdays,
            'pattern' => $this->patternLabel(),

            'tanker_count' => $this->tanker_count,
            // Money is a num on the wire — may arrive as an int. Null means
            // "price each order from the tanker that delivers it".
            'tanker_rate' => $this->tanker_rate === null ? null : (float) $this->tanker_rate,

            'delivery_slot_id' => $this->delivery_slot_id,
            'delivery_slot' => $this->slot?->label(),
            'delivery_time' => $this->delivery_time,

            'delivery_address' => $this->delivery_address ?: $this->customer?->address,

            'preferred_driver_id' => $this->preferred_driver_id,
            'preferred_driver' => $this->preferredDriver?->full_name,

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_delivery_date' => $this->next_run_date?->toDateString(),

            'is_paused' => (bool) $this->is_paused,
            'pause_until' => $this->pause_until?->toDateString(),
            'status' => (bool) $this->status,
            'notes' => $this->notes,
        ];
    }
}
