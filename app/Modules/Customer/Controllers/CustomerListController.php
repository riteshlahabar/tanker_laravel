<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\DataTables\CustomerDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerListController
{
    public function __invoke(CustomerDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize(PermissionEnum::CustomerView->value);

        return $dataTable->render('customer::index');
    }
}
