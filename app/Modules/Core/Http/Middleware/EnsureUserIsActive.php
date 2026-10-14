<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated account must not keep working just because it still holds a
 * valid session or token.
 */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (bool) $user->status === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            $user->currentAccessToken()?->delete();

            return ApiResponse::forbidden('Your account has been deactivated.');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['email' => 'Your account has been deactivated.']);
    }
}
