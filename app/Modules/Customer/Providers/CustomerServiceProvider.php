<?php

declare(strict_types=1);

namespace App\Modules\Customer\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use App\Modules\Customer\Repositories\CustomerRepository;
use App\Modules\Customer\Services\CustomerService;
use Closure;

final class CustomerServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Customer';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            CustomerRepositoryInterface::class => CustomerRepository::class,
            CustomerServiceInterface::class => CustomerService::class,
        ];
    }

    /**
     * "{customer}" resolves only through the customer-scoped repository, so a
     * driver's id in the URL gives a 404 rather than letting the driver be
     * edited as a customer.
     *
     * @return array<string, Closure(string): mixed>
     */
    protected function routeBindings(): array
    {
        return [
            'customer' => fn (string $id) => app(CustomerRepositoryInterface::class)->findOrFail((int) $id),
        ];
    }
}
