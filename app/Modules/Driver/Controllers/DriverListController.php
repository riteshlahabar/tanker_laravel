<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\DataTables\DriverDataTable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class DriverListController
{
    public function __invoke(DriverDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize(PermissionEnum::DriverView->value);

        return $dataTable->render('driver::index');
    }
}
