<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Order\DataTables\OrderDataTable;
use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class OrderListController
{
    public function __construct(private readonly DriverRepositoryInterface $drivers) {}

    public function __invoke(OrderDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        return $dataTable->render('order::index', [
            'statuses' => OrderStatusEnum::options(),
            'driverOptions' => $this->drivers->scopedQuery()
                ->orderBy('first_name')
                ->pluck('first_name', 'id')
                ->all(),
        ]);
    }
}
