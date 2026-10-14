<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

use App\Models\User;

interface TokenIssuerInterface
{
    /**
     * Issue a short-lived access token plus a long-lived refresh token.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, abilities: array<int, string>}
     */
    public function issue(User $user, string $deviceName): array;

    /**
     * Exchange a valid refresh token for a fresh pair, revoking the old pair.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int, abilities: array<int, string>}
     */
    public function refresh(User $user, string $deviceName): array;

    public function revokeCurrent(User $user): void;
}
