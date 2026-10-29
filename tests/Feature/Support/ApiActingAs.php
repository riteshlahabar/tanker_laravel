<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\User;
use App\Modules\Auth\Services\SanctumTokenIssuer;

/**
 * Signs requests with a real Sanctum token carrying the role's abilities,
 * rather than Sanctum::actingAs with ['*'] — so the tests exercise the same
 * ability checks the app will hit.
 */
trait ApiActingAs
{
    private function actingAsApi(User $user, string $device = 'test-device'): self
    {
        $token = app(SanctumTokenIssuer::class)->issue($user, $device)['access_token'];

        // The guard caches the user it resolved, and that instance outlives a
        // request inside one test. Without this, a second `actingAsApi` in
        // the same test sends the new token and is still answered as the
        // first user — which surfaces as a baffling 403 from `role:` on a
        // route the second user is plainly allowed to call.
        $this->app['auth']->forgetGuards();

        $this->withToken($token);

        return $this;
    }

    private function apiRefreshTokenFor(User $user, string $device = 'test-device'): string
    {
        return app(SanctumTokenIssuer::class)->issue($user, $device)['refresh_token'];
    }
}
