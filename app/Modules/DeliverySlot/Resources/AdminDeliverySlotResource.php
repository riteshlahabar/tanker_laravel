<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Resources;

use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full slot, for the admin CRUD screen. `DeliverySlotResource` stays the
 * lean id/name/label/start_time/end_time shape the order-form picker uses —
 * this one adds `sort_order` and `status`, which that picker has no use for.
 *
 * @mixin DeliverySlot
 */
final class AdminDeliverySlotResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'status' => (bool) $this->status,
        ];
    }
}
