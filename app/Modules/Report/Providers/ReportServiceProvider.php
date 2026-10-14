<?php

declare(strict_types=1);

namespace App\Modules\Report\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use App\Modules\Report\Services\ReportRegistry;

final class ReportServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Report';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            ReportRegistryInterface::class => ReportRegistry::class,
        ];
    }
}
