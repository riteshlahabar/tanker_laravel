<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers\Api;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use App\Modules\Customer\Requests\UpdateCustomerRequest;
use App\Modules\Customer\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerUpdateController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function __invoke(UpdateCustomerRequest $request, User $customer): JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        return ApiResponse::success(
            new CustomerResource($this->customers->update($customer, $request->validated())),
            __('Customer updated successfully.'),
        );
    }
}
