<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Order\Contracts\OrderAlertsInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Stop the buzzer: the order has been looked at.
 *
 * Called when the recipient opens the order, and by the notification's Stop
 * action. The policy decides whether the caller is the audience — an admin
 * can read every order but may only silence the ones written *for* them.
 */
final class MarkOrderSeenController
{
    public function __construct(private readonly OrderAlertsInterface $alerts) {}

    public function __invoke(Order $order): JsonResponse
    {
        Gate::authorize('markSeen', $order);

        return ApiResponse::success(
            new OrderResource($this->alerts->markSeen($order)),
            'Order marked as seen.',
        );
    }
}
