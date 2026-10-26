<?php

declare(strict_types=1);

namespace App\Modules\Spot\Resources;

use App\Modules\Spot\Models\Spot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Spot
 */
final class SpotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'refill_charge' => (float) $this->refill_charge,
            'water_type' => $this->water_type,
            'contact_person' => $this->contact_person,
            'contact_mobile' => $this->contact_mobile,
            'is_available' => (bool) $this->is_available,
            'status' => (bool) $this->status,
        ];
    }
}
