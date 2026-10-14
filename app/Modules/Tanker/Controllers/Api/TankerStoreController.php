<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Requests\StoreTankerRequest;
use App\Modules\Tanker\Resources\TankerResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class TankerStoreController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function __invoke(StoreTankerRequest $request): JsonResponse
    {
        return ApiResponse::success(
            new TankerResource($this->tankers->create($request->validated())),
            __('Tanker added successfully.'),
            Response::HTTP_CREATED,
        );
    }
}
