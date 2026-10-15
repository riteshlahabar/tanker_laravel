<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Schedule\Exports\DeliveryScheduleExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ScheduleExportController
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        Gate::authorize(PermissionEnum::ReportExport->value);

        $export = new DeliveryScheduleExport(
            status: $this->boolOrNull($request, 'status'),
            paused: $this->boolOrNull($request, 'paused'),
            customerId: $this->intOrNull($request, 'customer_id'),
        );

        return Excel::download($export, 'standing_orders_'.date('YmdHis').'.xlsx');
    }

    private function boolOrNull(Request $request, string $key): ?bool
    {
        $value = $request->input($key);

        return $value === null || $value === '' ? null : $request->boolean($key);
    }

    private function intOrNull(Request $request, string $key): ?int
    {
        $value = $request->input($key);

        return $value === null || $value === '' ? null : (int) $value;
    }
}
