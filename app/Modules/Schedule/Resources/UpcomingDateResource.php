<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Resources;

use App\Modules\Schedule\Support\UpcomingDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One date on the customer's "My Deliveries" list.
 *
 * `can_skip` is resolved server-side so the app disables the button rather
 * than letting the tap fail, and `skip_blocked_reason` is what it shows when
 * the button is disabled.
 *
 * @mixin UpcomingDate
 */
final class UpcomingDateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UpcomingDate $date */
        $date = $this->resource;

        return [
            'date' => $date->date->toDateString(),
            'date_label' => $date->date->format('D, d M Y'),
            'due_at' => $date->dueAt->toIso8601String(),
            'order_id' => $date->orderId(),
            'order_no' => $date->order?->order_no,
            'status' => $date->status(),
            'status_label' => $date->statusLabel(),
            'skipped' => $date->skipped,
            'can_skip' => $date->canSkip,
            'skip_blocked_reason' => $date->skipReason,
            'skip_reason' => $date->cancelledReason,
        ];
    }
}
