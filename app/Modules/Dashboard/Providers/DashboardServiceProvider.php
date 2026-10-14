<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use App\Modules\Dashboard\Listeners\FlushDashboardSummary;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Order\Events\OrderStatusChanged;

final class DashboardServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Dashboard';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            DashboardServiceInterface::class => DashboardService::class,
        ];
    }

    /**
     * @return array<class-string, array<int, class-string>>
     */
    protected function events(): array
    {
        return [
            OrderStatusChanged::class => [FlushDashboardSummary::class],
        ];
    }
}
