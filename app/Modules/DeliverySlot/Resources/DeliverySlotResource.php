<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Resources;

use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliverySlot
 */
final class DeliverySlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
