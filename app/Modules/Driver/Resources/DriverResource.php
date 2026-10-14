<?php

declare(strict_types=1);

namespace App\Modules\Driver\Resources;

use App\Models\User;
use App\Modules\Tanker\Resources\TankerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class DriverResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'per_delivery_rate' => (float) $this->per_delivery_rate,
            'status' => (bool) $this->status,
            'tanker' => $this->currentAssignment?->tanker
                ? new TankerResource($this->currentAssignment->tanker)
                : null,
        ];
    }
}
