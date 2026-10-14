<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers\Api;

use App\Modules\Core\Controllers\Api\Concerns\RespondsWithList;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CustomerListController
{
    use RespondsWithList;

    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerView->value);

        return $this->listResponse(
            $this->customers->scopedQuery()->orderBy('first_name'),
            $request,
            CustomerResource::class,
            ['first_name', 'last_name', 'mobile', 'address'],
        );
    }
}
