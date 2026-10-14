<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Policies\TankerPolicy;
use App\Modules\Tanker\Repositories\TankerRepository;
use App\Modules\Tanker\Services\TankerService;

final class TankerServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Tanker';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            TankerRepositoryInterface::class => TankerRepository::class,
            TankerServiceInterface::class => TankerService::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            Tanker::class => TankerPolicy::class,
        ];
    }
}
