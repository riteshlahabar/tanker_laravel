<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: role:admin — or role:admin,driver for either.
 */
final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user !== null && $user->hasAnyRole($roles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return ApiResponse::forbidden('This action is not allowed for your account.');
        }

        abort(Response::HTTP_FORBIDDEN);
    }
}
