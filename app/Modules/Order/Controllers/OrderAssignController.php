<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\AssignOrderRequest;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class OrderAssignController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly DriverRepositoryInterface $drivers,
        private readonly SpotRepositoryInterface $spots,
    ) {}

    public function form(Order $order): View
    {
        Gate::authorize('assign', $order);

        return view('order::assign', [
            'order' => $order->load('customer'),
            'drivers' => $this->drivers->scopedQuery()
                ->where('status', true)
                ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number')
                ->orderBy('first_name')
                ->get(),
            'spots' => $this->spots->usable(),
        ]);
    }

    public function update(AssignOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('assign', $order);

        try {
            $this->orders->assign(
                order: $order,
                driverId: (int) $request->input('driver_id'),
                spotId: $request->input('spot_id') === null ? null : (int) $request->input('spot_id'),
                actor: $request->user(),
            );
        } catch (OrderAssignmentException|InvalidStatusTransitionException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.order.show', $order)
            ->with('success', __('Order assigned.'));
    }
}
