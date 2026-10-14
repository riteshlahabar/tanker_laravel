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

        $this->withToken($token);

        return $this;
    }

    private function apiRefreshTokenFor(User $user, string $device = 'test-device'): string
    {
        return app(SanctumTokenIssuer::class)->issue($user, $device)['refresh_token'];
    }
}
