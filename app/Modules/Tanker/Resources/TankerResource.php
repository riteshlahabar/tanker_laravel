<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Resources;

use App\Modules\Tanker\Models\Tanker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tanker
 */
final class TankerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_name' => $this->vehicle_name,
            'vehicle_number' => $this->vehicle_number,
            'label' => $this->label(),
            'capacity_litres' => $this->capacity_litres,
            'tanker_rate' => $this->tanker_rate === null ? null : (float) $this->tanker_rate,
            'model_year' => $this->model_year,
            'rc_number' => $this->rc_number,
            'insurance_expiry' => $this->insurance_expiry?->toDateString(),
            'status' => (bool) $this->status,
            'current_driver' => $this->whenLoaded(
                'currentAssignment',
                fn () => $this->currentAssignment?->driver?->full_name,
            ),
        ];
    }
}
