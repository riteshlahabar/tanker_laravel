<?php

declare(strict_types=1);

namespace App\Modules\Expense\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Contracts\ExpenseServiceInterface;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Policies\ExpensePolicy;
use App\Modules\Expense\Repositories\ExpenseRepository;
use App\Modules\Expense\Services\ExpenseService;

final class ExpenseServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Expense';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            ExpenseRepositoryInterface::class => ExpenseRepository::class,
            ExpenseServiceInterface::class => ExpenseService::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            DriverExpense::class => ExpensePolicy::class,
        ];
    }
}
