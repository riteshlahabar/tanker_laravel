<?php

declare(strict_types=1);

namespace App\Modules\Order\Controllers;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class OrderShowController
{
    public function __invoke(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'customer', 'driver', 'tanker', 'spot',
            'statusLogs.changedBy:id,first_name,last_name',
        ]);

        return view('order::show', ['order' => $order]);
    }
}
