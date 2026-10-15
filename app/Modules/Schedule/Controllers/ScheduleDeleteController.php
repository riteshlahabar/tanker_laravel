<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduleDeleteController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function __invoke(DeliverySchedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $this->schedules->delete($schedule);

        return redirect()
            ->route('admin.schedule.index')
            // Soft deleted, so the orders it produced stay in the reports.
            ->with('success', __('Standing order removed. Past orders are untouched.'));
    }
}
