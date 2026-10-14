<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\UpdateOrderStatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Moves an order along the road: refilling at the spot, out for delivery,
 * delivered. The allowed moves come from OrderStatusEnum.
 */
final class OrderStatusController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('updateStatus', $order);

        try {
            $this->orders->moveTo(
                order: $order,
                status: $request->status(),
                actor: $request->user(),
                note: $request->string('note')->value() ?: null,
            );
        } catch (InvalidStatusTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Order status updated.'));
    }
}
