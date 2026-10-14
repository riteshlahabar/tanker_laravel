<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Models\OrderStatusLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusLog
 */
final class OrderStatusLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status->value,
            'label' => $this->status->label(),
            'note' => $this->note,
            'by' => $this->whenLoaded('changedBy', fn () => $this->changedBy?->full_name),
            'at' => $this->created_at?->toIso8601String(),
        ];
    }
}
