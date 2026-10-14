<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Contracts\TokenIssuerInterface;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Support\Carbon;

/**
 * Short-lived access token scoped to the role's permissions, plus a refresh
 * token whose only ability is to mint a new pair. Both are per-device, so one
 * phone can be revoked without signing the user out everywhere.
 */
final class SanctumTokenIssuer implements TokenIssuerInterface
{
    public const REFRESH_ABILITY = 'token:refresh';

    private const ACCESS_TOKEN = 'access';

    private const REFRESH_TOKEN = 'refresh';

    public function __construct(
        private readonly int $accessMinutes = 120,
        private readonly int $refreshDays = 30,
    ) {}

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, abilities: array<int, string>}
     */
    public function issue(User $user, string $deviceName): array
    {
        $this->revokeDevice($user, $deviceName);

        $abilities = $this->abilitiesFor($user);

        $access = $user->createToken(
            $this->tokenName(self::ACCESS_TOKEN, $deviceName),
            $abilities,
            Carbon::now()->addMinutes($this->accessMinutes),
        );

        $refresh = $user->createToken(
            $this->tokenName(self::REFRESH_TOKEN, $deviceName),
            [self::REFRESH_ABILITY],
            Carbon::now()->addDays($this->refreshDays),
        );

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'expires_in' => $this->accessMinutes * 60,
            'abilities' => $abilities,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, abilities: array<int, string>}
     */
    public function refresh(User $user, string $deviceName): array
    {
        return $this->issue($user, $deviceName);
    }

    public function revokeCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token === null) {
            return;
        }

        $device = $this->deviceOf((string) $token->name);

        $device === null
            ? $token->delete()
            : $this->revokeDevice($user, $device);
    }

    /**
     * @return array<int, string>
     */
    private function abilitiesFor(User $user): array
    {
        $role = RoleEnum::tryFrom((string) $user->roleName());

        return $role === null ? [] : PermissionEnum::forRole($role);
    }

    private function revokeDevice(User $user, string $deviceName): void
    {
        $user->tokens()
            ->whereIn('name', [
                $this->tokenName(self::ACCESS_TOKEN, $deviceName),
                $this->tokenName(self::REFRESH_TOKEN, $deviceName),
            ])
            ->delete();
    }

    private function tokenName(string $type, string $deviceName): string
    {
        return $type.':'.$deviceName;
    }

    private function deviceOf(string $tokenName): ?string
    {
        $parts = explode(':', $tokenName, 2);

        return $parts[1] ?? null;
    }
}
