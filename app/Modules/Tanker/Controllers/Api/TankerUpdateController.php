<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Requests\UpdateTankerRequest;
use App\Modules\Tanker\Resources\TankerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TankerUpdateController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function __invoke(UpdateTankerRequest $request, Tanker $tanker): JsonResponse
    {
        Gate::authorize('update', $tanker);

        return ApiResponse::success(
            new TankerResource($this->tankers->update($tanker, $request->validated())),
            __('Tanker updated successfully.'),
        );
    }
}
