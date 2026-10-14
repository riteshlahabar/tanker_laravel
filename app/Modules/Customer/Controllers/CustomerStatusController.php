<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Support\ApiResponse;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CustomerStatusController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function __invoke(Request $request, User $customer): JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->customers->setStatus($customer, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
