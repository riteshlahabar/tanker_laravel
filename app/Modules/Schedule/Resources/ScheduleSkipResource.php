<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Resources;

use App\Modules\Schedule\Models\ScheduleSkip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ScheduleSkip
 */
final class ScheduleSkipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->skip_date?->toDateString(),
            'reason' => $this->reason,
            'requested_by' => $this->requester?->full_name,
            'cancelled_order_no' => $this->order?->order_no,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
