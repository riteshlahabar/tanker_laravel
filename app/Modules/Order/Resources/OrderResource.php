<?php

declare(strict_types=1);

namespace App\Modules\Order\Resources;

use App\Modules\Order\Models\Order;
use App\Modules\Spot\Resources\SpotResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full order, for a detail screen. The driver's mobile is exposed to the
 * customer on purpose (so they can call about the delivery) but nothing else
 * about the driver's account is.
 *
 * @mixin Order
 */
final class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new OrderListResource($this->resource))->toArray($request),
            'latitude' => $this->latitude === null ? null : (float) $this->latitude,
            'longitude' => $this->longitude === null ? null : (float) $this->longitude,
            'quantity_litres' => $this->quantity_litres,
            'delivery_slot' => $this->delivery_slot,
            'refill_charge' => (float) $this->refill_charge,
            'driver_earning' => (float) $this->driver_earning,
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'refilled_at' => $this->refilled_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', fn (): array => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
                'mobile' => $this->customer->mobile,
            ]),
            'driver' => $this->whenLoaded('driver', fn (): ?array => $this->driver === null ? null : [
                'id' => $this->driver->id,
                'full_name' => $this->driver->full_name,
                'mobile' => $this->driver->mobile,
            ]),
            'spot' => $this->whenLoaded('spot', fn () => $this->spot === null
                ? null
                : new SpotResource($this->spot)),
            'timeline' => OrderStatusLogResource::collection($this->whenLoaded('statusLogs')),
        ];
    }
}
