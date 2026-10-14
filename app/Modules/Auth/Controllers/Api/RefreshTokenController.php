<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Contracts\TokenIssuerInterface;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RefreshTokenController
{
    public function __construct(private readonly TokenIssuerInterface $tokens) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $device = $request->string('device_name')->value() ?: 'mobile';

        return ApiResponse::success(
            ['token' => $this->tokens->refresh($user, $device)],
            'Token refreshed.',
        );
    }
}
