<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Services\SanctumTokenIssuer;
use App\Modules\Core\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `auth:sanctum` only proves the token belongs to the user — it does not look
 * at abilities. Without this, the long-lived refresh token would work as a
 * full access token everywhere, defeating the point of issuing a narrow one.
 *
 * Applied to every authenticated API route except /auth/refresh.
 */
final class EnsureAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        // A session-authenticated user (the web panel) has no token at all.
        if ($token === null || ! method_exists($token, 'can')) {
            return $next($request);
        }

        if ($token->can(SanctumTokenIssuer::REFRESH_ABILITY)) {
            return ApiResponse::forbidden(
                'This is a refresh token. Exchange it at /api/auth/refresh for an access token.',
            );
        }

        return $next($request);
    }
}
