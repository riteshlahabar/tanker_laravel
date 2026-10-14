<?php

declare(strict_types=1);

namespace App\Modules\Auth\Providers;

use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Contracts\TokenIssuerInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\SanctumTokenIssuer;
use App\Modules\Core\Providers\ModuleServiceProvider;

final class AuthServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Auth';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            AuthServiceInterface::class => AuthService::class,
            TokenIssuerInterface::class => SanctumTokenIssuer::class,
        ];
    }
}
