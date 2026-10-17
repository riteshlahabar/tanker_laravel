<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use Illuminate\Contracts\View\View;

final class DashboardController
{
    public function __construct(private readonly DashboardServiceInterface $dashboard) {}

    public function __invoke(): View
    {
        $open = $this->dashboard->openOrders();

        return view('dashboard::index', [
            'summary' => $this->dashboard->summary(),
            'openOrders' => $open['orders'],
            'openTotal' => $open['total'],
        ]);
    }
}
