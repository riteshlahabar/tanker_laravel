<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderAlertsInterface;
use App\Modules\Order\Resources\OrderListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What is still ringing for whoever is asking — the app polls this.
 *
 * One controller for both roles, as `OrderDetailController` already is: the
 * audience comes from the caller's own role, so a driver cannot ask for the
 * admin's alerts by changing a URL.
 *
 * Not paginated, and capped. An alert list is "what is on fire right now",
 * and a second page of things nobody has looked at is a problem to solve by
 * looking at them, not by scrolling. The cap also keeps a poll cheap.
 */
final class OrderAlertsController
{
    private const LIMIT = 20;

    public function __construct(private readonly OrderAlertsInterface $alerts) {}

    public function __invoke(Request $request): JsonResponse
    {
        $orders = $this->alerts->pendingFor($request->user())
            ->with(['customer:id,first_name,last_name,mobile', 'driver:id,first_name,last_name'])
            // Oldest first: the one that has been ignored longest is the one
            // worth ringing about.
            ->oldest('id')
            ->limit(self::LIMIT)
            ->get();

        return ApiResponse::success([
            'items' => OrderListResource::collection($orders),
            'count' => $orders->count(),
        ]);
    }
}
