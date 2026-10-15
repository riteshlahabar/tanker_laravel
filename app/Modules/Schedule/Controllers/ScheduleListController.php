<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Schedule\DataTables\DeliveryScheduleDataTable;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduleListController
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function __invoke(DeliveryScheduleDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', DeliverySchedule::class);

        return $dataTable->render('schedule::index', [
            'customerOptions' => ['' => 'All customers'] + $this->customers->scopedQuery()
                ->orderBy('first_name')
                ->pluck('first_name', 'id')
                ->all(),
        ]);
    }
}
