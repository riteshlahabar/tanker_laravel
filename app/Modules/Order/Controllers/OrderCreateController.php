<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Admin-side order entry, for orders taken over the phone.
 */
final class OrderCreateController
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
        private readonly CustomerRepositoryInterface $customers,
        private readonly SettingRepositoryInterface $settings,
    ) {}

    public function form(): View
    {
        Gate::authorize('create', Order::class);

        return view('order::create', [
            'customers' => $this->customers->scopedQuery()
                ->where('status', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'mobile', 'address']),
            'defaultAmount' => $this->settings->value('default_order_amount', '0'),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->place($request->orderData(), $request->user());

        return redirect()
            ->route('admin.order.show', $order)
            ->with('success', __('Order :no placed.', ['no' => $order->order_no]));
    }
}
