<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Contracts\TokenIssuerInterface;
use App\Modules\Core\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController
{
    public function __construct(private readonly TokenIssuerInterface $tokens) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->tokens->revokeCurrent($request->user());

        return ApiResponse::success(null, 'Signed out.');
    }
}
