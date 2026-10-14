<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Tanker\Exports\TankerExport;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class TankerExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Tanker::class);

        $export = new TankerExport(
            search: $request->string('search')->value() ?: null,
            status: $request->has('status') && $request->input('status') !== ''
                ? $request->boolean('status')
                : null,
        );

        return Excel::download($export, 'tankers_'.now()->format('Ymd_His').'.xlsx');
    }
}
