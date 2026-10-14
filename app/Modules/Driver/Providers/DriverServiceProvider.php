<?php

declare(strict_types=1);

namespace App\Modules\Driver\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Contracts\TankerAssignmentServiceInterface;
use App\Modules\Driver\Repositories\DriverRepository;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Driver\Services\TankerAssignmentService;
use Closure;

final class DriverServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Driver';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            DriverRepositoryInterface::class => DriverRepository::class,
            DriverServiceInterface::class => DriverService::class,
            TankerAssignmentServiceInterface::class => TankerAssignmentService::class,
        ];
    }

    /**
     * "{driver}" resolves only to a user holding the driver role.
     *
     * @return array<string, Closure(string): mixed>
     */
    protected function routeBindings(): array
    {
        return [
            'driver' => fn (string $id) => app(DriverRepositoryInterface::class)->findOrFail((int) $id),
        ];
    }
}
