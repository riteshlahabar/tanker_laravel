<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Services\BaseService;
use Illuminate\Support\Facades\Hash;

final class AuthService extends BaseService implements AuthServiceInterface
{
    public function authenticate(string $identifier, string $password): User
    {
        $user = $this->findByIdentifier($identifier);

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        if (! $user->status) {
            throw new AccountInactiveException;
        }

        return $user;
    }

    public function changePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw new InvalidCredentialsException('Your current password is incorrect.');
        }

        $this->transaction(function () use ($user, $new): void {
            $user->update(['password' => $new]);
            $user->tokens()->delete();
        });
    }

    /**
     * An identifier containing "@" is an admin email; anything else is the
     * mobile number a driver or customer signs in with.
     */
    private function findByIdentifier(string $identifier): ?User
    {
        $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        return User::query()
            ->with('roles:id,name')
            ->where($column, $identifier)
            ->first();
    }
}
