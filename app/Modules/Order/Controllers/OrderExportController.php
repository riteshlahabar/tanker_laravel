<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Order\Exports\OrderExport;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OrderExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Order::class);

        $export = new OrderExport(
            user: $request->user(),
            filters: $request->only(['status', 'driver_id', 'from', 'to', 'search']),
        );

        return Excel::download($export, 'orders_'.now()->format('Ymd_His').'.xlsx');
    }
}
