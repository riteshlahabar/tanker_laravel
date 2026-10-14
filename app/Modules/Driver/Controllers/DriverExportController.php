<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Exports\DriverExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DriverExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize(PermissionEnum::DriverView->value);

        $export = new DriverExport(
            search: $request->string('search')->value() ?: null,
            status: $request->has('status') && $request->input('status') !== ''
                ? $request->boolean('status')
                : null,
        );

        return Excel::download($export, 'drivers_'.now()->format('Ymd_His').'.xlsx');
    }
}
