<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use App\Modules\Customer\Requests\StoreCustomerRequest;
use App\Modules\Customer\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CustomerStoreController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function __invoke(StoreCustomerRequest $request): JsonResponse
    {
        return ApiResponse::success(
            new CustomerResource($this->customers->create($request->validated())),
            __('Customer added successfully.'),
            Response::HTTP_CREATED,
        );
    }
}
