<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\CancelOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class OrderCancelController
{
    public function __construct(private readonly OrderServiceInterface $orders) {}

    public function __invoke(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        try {
            $this->orders->cancel(
                $order,
                $request->string('cancel_reason')->value(),
                $request->user(),
            );
        } catch (InvalidStatusTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Order cancelled.'));
    }
}
