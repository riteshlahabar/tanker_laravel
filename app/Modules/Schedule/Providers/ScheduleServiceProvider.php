<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Schedule\Console\GenerateScheduledOrdersCommand;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Policies\DeliverySchedulePolicy;
use App\Modules\Schedule\Repositories\DeliveryScheduleRepository;
use App\Modules\Schedule\Repositories\ScheduleSkipRepository;
use App\Modules\Schedule\Services\DeliveryScheduleService;
use App\Modules\Schedule\Services\ScheduledOrderGenerator;
use App\Modules\Schedule\Services\ScheduleSkipService;

final class ScheduleServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Schedule';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            DeliveryScheduleRepositoryInterface::class => DeliveryScheduleRepository::class,
            ScheduleSkipRepositoryInterface::class => ScheduleSkipRepository::class,
            DeliveryScheduleServiceInterface::class => DeliveryScheduleService::class,
            ScheduleSkipServiceInterface::class => ScheduleSkipService::class,
            ScheduledOrderGeneratorInterface::class => ScheduledOrderGenerator::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            DeliverySchedule::class => DeliverySchedulePolicy::class,
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateScheduledOrdersCommand::class]);
        }
    }
}
