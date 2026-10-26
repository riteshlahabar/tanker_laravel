<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\StoreOrderRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin-side order entry, for orders taken over the phone.
 *
 * The driver is chosen on this form rather than a later screen, because the
 * price of the order is the rate of the tanker that driver holds: pick the
 * driver, see the rate, choose how many loads, read the total.
 */
final class OrderCreateController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly CustomerRepositoryInterface $customers,
        private readonly DriverRepositoryInterface $drivers,
        private readonly DeliverySlotRepositoryInterface $slots,
    ) {}

    public function form(): View
    {
        Gate::authorize('create', Order::class);

        return view('order::create', [
            'customers' => $this->customers->scopedQuery()
                ->where('status', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'mobile', 'address']),
            // Only drivers who can actually take the order: active, and
            // holding a tanker. Assignment refuses the rest, so offering them
            // would only produce a failure after the form was filled in.
            'drivers' => $this->drivers->scopedQuery()
                ->where('status', true)
                ->whereHas('currentAssignment')
                ->with('currentAssignment.tanker')
                ->orderBy('first_name')
                ->get(),
            'slots' => $this->slots->options(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        Gate::authorize('create', Order::class);

        try {
            $order = $this->orders->placeForDriver(
                $request->orderData(),
                $request->driverId(),
                $request->tankerRate(),
                $request->user(),
            );
        } catch (OrderAssignmentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.order.show', $order)
            ->with('success', __('Order :no placed and assigned.', ['no' => $order->order_no]));
    }
}
