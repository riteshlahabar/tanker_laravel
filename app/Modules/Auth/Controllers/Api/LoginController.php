<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Contracts\TokenIssuerInterface;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Requests\ApiLoginRequest;
use App\Modules\Auth\Resources\AuthUserResource;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __construct(
        private readonly AuthServiceInterface $auth,
        private readonly TokenIssuerInterface $tokens,
    ) {}

    public function __invoke(ApiLoginRequest $request): JsonResponse
    {
        try {
            $user = $this->auth->authenticate(
                $request->string('username')->value(),
                $request->string('password')->value(),
            );
        } catch (InvalidCredentialsException|AccountInactiveException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        return ApiResponse::success([
            'user' => new AuthUserResource($user),
            'token' => $this->tokens->issue($user, $request->deviceName()),
        ], 'Signed in successfully.');
    }
}
