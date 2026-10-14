<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ChangePasswordController
{
    public function __construct(private readonly AuthServiceInterface $auth) {}

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->auth->changePassword(
                $request->user(),
                $request->string('current_password')->value(),
                $request->string('password')->value(),
            );
        } catch (InvalidCredentialsException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(null, 'Password changed. Please sign in again.');
    }
}
