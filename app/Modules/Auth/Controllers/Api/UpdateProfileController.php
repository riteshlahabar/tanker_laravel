<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Requests\UpdateProfileRequest;
use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class UpdateProfileController
{
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return ApiResponse::success(
            new AuthUserResource($user->fresh()->load('roles:id,name')),
            __('Profile updated.'),
        );
    }
}
