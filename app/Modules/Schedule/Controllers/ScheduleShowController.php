<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\ScheduleSkipRepositoryInterface;
use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The schedule's own page: the pattern in words, the dates coming up with
 * their skip state, the orders it has produced, and the skips panel.
 */
final class ScheduleShowController
{
    public function __construct(
        private readonly ScheduleSkipServiceInterface $skipService,
        private readonly ScheduleSkipRepositoryInterface $skips,
    ) {}

    public function __invoke(Request $request, DeliverySchedule $schedule): View
    {
        Gate::authorize('view', $schedule);

        return view('schedule::show', [
            'schedule' => $schedule->load(['customer', 'slot', 'preferredDriver', 'creator']),
            'upcoming' => $this->skipService->upcoming($schedule, $request->user()),
            'skips' => $this->skips->forSchedule($schedule),
            'orders' => $schedule->orders()
                ->with(['driver:id,first_name,last_name', 'tanker:id,vehicle_number'])
                ->limit(20)
                ->get(),
        ]);
    }
}
