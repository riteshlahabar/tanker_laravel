<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\PauseScheduleRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Holidays and customers who are away. Paused dates are simply missed — there
 * is no catch-up when it resumes.
 */
final class SchedulePauseController
{
    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function pause(PauseScheduleRequest $request, DeliverySchedule $schedule): RedirectResponse
    {
        $until = $request->pauseUntil();

        $this->schedules->pause($schedule, $until);

        return back()->with('success', $until === null
            ? __('Standing order paused until you resume it.')
            : __('Standing order paused until :date.', ['date' => $until->format('d M Y')]));
    }

    public function resume(PauseScheduleRequest $request, DeliverySchedule $schedule): RedirectResponse
    {
        $resumed = $this->schedules->resume($schedule);

        return back()->with('success', __('Standing order resumed. Next delivery :date.', [
            'date' => $resumed->next_run_date?->format('D, d M Y') ?? 'not scheduled',
        ]));
    }
}
