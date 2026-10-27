<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers\Api\Driver;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Resources\CustomerLookupResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Find the customer a driver is about to write an order for.
 *
 * The same search the admin's picker uses — `RespondsWithList` over the same
 * four columns — but gated on `customer.lookup` rather than `customer.view`,
 * and answering with the narrow [CustomerLookupResource].
 *
 * Only **active** customers are offered: a deactivated account cannot be
 * ordered for, so listing one would only produce a refusal at submit.
 */
final class CustomerLookupController
{
    use RespondsWithList;

    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerLookup->value);

        return $this->listResponse(
            $this->customers->scopedQuery()
                ->where('status', true)
                ->orderBy('first_name'),
            $request,
            CustomerLookupResource::class,
            ['first_name', 'last_name', 'mobile', 'address'],
        );
    }
}
