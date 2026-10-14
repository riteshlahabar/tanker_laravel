<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api\Customer;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Resources\OrderListResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's own orders. Scoping comes from the repository, so no filter
 * in the query string can widen it.
 */
final class MyOrdersController
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = $this->orders->visibleTo($request->user())
            ->with(['driver:id,first_name,last_name', 'tanker:id,vehicle_number'])
            ->when($request->filled('status'), fn (Builder $q) => $q
                ->where('status', $request->input('status')))
            ->when($request->boolean('active_only'), fn (Builder $q) => $q->open())
            ->latest('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, OrderListResource::class));
    }
}
