<?php

declare(strict_types=1);

namespace App\Modules\Order\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Order\Contracts\OrderNumberGeneratorInterface;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Policies\OrderPolicy;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Services\OrderNumberGenerator;
use App\Modules\Order\Services\OrderService;

final class OrderServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Order';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            OrderRepositoryInterface::class => OrderRepository::class,
            OrderServiceInterface::class => OrderService::class,
            OrderNumberGeneratorInterface::class => OrderNumberGenerator::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            Order::class => OrderPolicy::class,
        ];
    }
}
