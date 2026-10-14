<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Resources\OrderListResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The admin's order list in the app — the same filters the panel table has.
 */
final class OrderListController
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $search = $request->string('search')->value();

        $page = $this->orders->visibleTo($request->user())
            ->with([
                'customer:id,first_name,last_name',
                'driver:id,first_name,last_name',
                'tanker:id,vehicle_number',
                'spot:id,name',
            ])
            ->when($request->filled('status'), fn (Builder $q) => $q
                ->where('status', $request->input('status')))
            ->when($request->filled('driver_id'), fn (Builder $q) => $q
                ->where('driver_id', $request->input('driver_id')))
            ->when($request->filled('from'), fn (Builder $q) => $q
                ->whereDate('delivery_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q
                ->whereDate('delivery_date', '<=', $request->input('to')))
            ->when($search !== '', fn (Builder $q) => $q
                ->where('order_no', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, OrderListResource::class));
    }
}
