<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\ScheduleSkipServiceInterface;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\SkipDateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * The admin's side of calling off a date. The admin overrides the six hours'
 * notice but is still bound by the status backstop, which SkipWindow enforces.
 */
final class ScheduleSkipController
{
    public function __construct(private readonly ScheduleSkipServiceInterface $skips) {}

    public function store(SkipDateRequest $request, DeliverySchedule $schedule): RedirectResponse
    {
        Gate::authorize('skip', $schedule);

        try {
            $this->skips->skip($schedule, $request->date(), $request->user(), $request->reason());
        } catch (ScheduleSkipException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Delivery on :date called off.', [
            'date' => $request->date()->format('D, d M Y'),
        ]));
    }

    public function destroy(Request $request, DeliverySchedule $schedule, string $date): RedirectResponse
    {
        Gate::authorize('skip', $schedule);

        $day = Carbon::parse($date)->startOfDay();

        try {
            $this->skips->restore($schedule, $day, $request->user());
        } catch (ScheduleSkipException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Delivery on :date restored.', [
            'date' => $day->format('D, d M Y'),
        ]));
    }
}
