<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Driver;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Resources\OrderListResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The driver's own orders. "today=1" is what the home screen asks for;
 * "status=" backs the assigned / in-progress / delivered tabs.
 */
final class MyDeliveriesController
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = $this->orders->visibleTo($request->user())
            ->with(['customer:id,first_name,last_name,mobile', 'tanker:id,vehicle_number', 'spot:id,name'])
            ->when($request->filled('status'), fn (Builder $q) => $q
                ->where('status', $request->input('status')))
            ->when($request->boolean('open_only'), fn (Builder $q) => $q->open())
            ->when($request->boolean('today'), fn (Builder $q) => $q
                ->whereDate('delivery_date', now()->toDateString()))
            ->orderByRaw('field(status, ?, ?, ?, ?, ?, ?)',
                ['assigned', 'refilling', 'out_for_delivery', 'pending', 'delivered', 'cancelled'])
            ->latest('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, OrderListResource::class));
    }
}
