<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers\Api;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerDeleteController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function __invoke(User $customer): JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        $this->customers->delete($customer);

        return ApiResponse::success(null, __('Customer deleted successfully.'));
    }
}
