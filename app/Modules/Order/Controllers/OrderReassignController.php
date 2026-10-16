<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\ReassignOrderRequest;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Changing the driver on an order that already has one — kept apart from
 * OrderAssignController because it is a different decision with a different
 * rule (the order must still be waiting) and a different audience (the driver
 * losing the job has to be told).
 */
final class OrderReassignController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly DriverRepositoryInterface $drivers,
        private readonly SpotRepositoryInterface $spots,
    ) {}

    public function form(Order $order): View
    {
        Gate::authorize('reassign', $order);

        return view('order::reassign', [
            'order' => $order->load(['customer', 'driver', 'tanker', 'spot']),
            // The driver who already holds it is excluded, so the only choices
            // on screen are ones the service will accept.
            'drivers' => $this->drivers->scopedQuery()
                ->where('status', true)
                ->whereKeyNot($order->driver_id)
                ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number')
                ->orderBy('first_name')
                ->get(),
            'spots' => $this->spots->usable(),
        ]);
    }

    public function update(ReassignOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('reassign', $order);

        try {
            $this->orders->reassign(
                order: $order,
                driverId: (int) $request->input('driver_id'),
                spotId: $request->input('spot_id') === null ? null : (int) $request->input('spot_id'),
                actor: $request->user(),
                reason: $request->input('reason'),
            );
        } catch (OrderAssignmentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.order.show', $order)
            ->with('success', __('Driver changed.'));
    }
}
