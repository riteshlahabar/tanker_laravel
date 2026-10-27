<?php

declare(strict_types=1);

namespace App\Modules\Customer\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Just enough of a customer for a driver to write an order for them.
 *
 * Four fields, and the omissions are the point. [CustomerResource] also sends
 * `first_name`/`last_name` separately, `can_self_order` and `status` — none of
 * which a picker uses, and all of which would then sit on every driver's
 * phone. A driver placing an order needs to *find* a customer and prefill the
 * delivery address, not read their account.
 *
 * `address` is here because the order form defaults the delivery address to
 * the customer's registered one, exactly as `CustomerAddressResolver` does
 * server-side.
 *
 * @mixin User
 */
final class CustomerLookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'address' => $this->address,
        ];
    }
}
