<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The lighter shape used in lists, so a long order list stays small over a
 * mobile connection.
 *
 * @mixin Order
 */
final class OrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_open' => $this->status->isOpen(),
            'delivery_address' => $this->delivery_address,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'order_amount' => (float) $this->order_amount,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->full_name),
            'driver_name' => $this->whenLoaded('driver', fn () => $this->driver?->full_name),
            'tanker_number' => $this->whenLoaded('tanker', fn () => $this->tanker?->vehicle_number),
            'spot_name' => $this->whenLoaded('spot', fn () => $this->spot?->name),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
