<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers;

use App\Modules\Spot\Exports\SpotExport;
use App\Modules\Spot\Models\Spot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SpotExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Spot::class);

        $export = new SpotExport(
            search: $request->string('search')->value() ?: null,
            status: $this->tristate($request, 'status'),
            available: $this->tristate($request, 'is_available'),
        );

        return Excel::download($export, 'spots_'.now()->format('Ymd_His').'.xlsx');
    }

    private function tristate(Request $request, string $key): ?bool
    {
        return $request->has($key) && $request->input($key) !== ''
            ? $request->boolean($key)
            : null;
    }
}
