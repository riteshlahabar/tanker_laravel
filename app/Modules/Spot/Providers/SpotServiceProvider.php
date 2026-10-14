<?php

declare(strict_types=1);

namespace App\Modules\Spot\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Spot\Policies\SpotPolicy;
use App\Modules\Spot\Repositories\SpotRepository;
use App\Modules\Spot\Services\SpotService;

final class SpotServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Spot';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            SpotRepositoryInterface::class => SpotRepository::class,
            SpotServiceInterface::class => SpotService::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            Spot::class => SpotPolicy::class,
        ];
    }
}
