<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts;

use App\Models\User;
use App\Modules\Auth\Exceptions\AccountInactiveException;
use App\Modules\Auth\Exceptions\InvalidCredentialsException;

interface AuthServiceInterface
{
    /**
     * Resolve a login identifier (email for the admin, mobile for a driver or
     * customer) to an active account whose password matches.
     *
     * @throws InvalidCredentialsException
     * @throws AccountInactiveException
     */
    public function authenticate(string $identifier, string $password): User;

    public function changePassword(User $user, string $current, string $new): void;
}
