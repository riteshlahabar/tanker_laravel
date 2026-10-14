<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new AuthUserResource($request->user()->load('roles:id,name')),
        );
    }
}
