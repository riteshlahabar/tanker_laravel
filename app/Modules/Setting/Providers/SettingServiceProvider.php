<?php

declare(strict_types=1);

namespace App\Modules\Setting\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Setting\Repositories\SettingRepository;

final class SettingServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Setting';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            SettingRepositoryInterface::class => SettingRepository::class,
        ];
    }
}
