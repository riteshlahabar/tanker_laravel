<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Exports\CustomerExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CustomerExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize(PermissionEnum::CustomerView->value);

        $export = new CustomerExport(
            search: $request->string('search')->value() ?: null,
            status: $request->has('status') && $request->input('status') !== ''
                ? $request->boolean('status')
                : null,
        );

        return Excel::download($export, 'customers_'.now()->format('Ymd_His').'.xlsx');
    }
}
