<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Controllers\Concerns\BuildsScheduleForm;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\UpdateDeliveryScheduleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduleUpdateController
{
    use BuildsScheduleForm;

    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function form(DeliverySchedule $schedule): View
    {
        Gate::authorize('update', $schedule);

        return view('schedule::edit', [
            'schedule' => $schedule,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateDeliveryScheduleRequest $request, DeliverySchedule $schedule): RedirectResponse
    {
        $this->schedules->update($schedule, $request->validated());

        return redirect()
            ->route('admin.schedule.show', $schedule)
            // Said plainly, because it is the question an owner always asks.
            ->with('success', __('Standing order updated. Orders already generated are unchanged.'));
    }
}
