<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Schedule\Contracts\DeliveryScheduleServiceInterface;
use App\Modules\Schedule\Controllers\Concerns\BuildsScheduleForm;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Schedule\Requests\StoreDeliveryScheduleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ScheduleCreateController
{
    use BuildsScheduleForm;

    public function __construct(private readonly DeliveryScheduleServiceInterface $schedules) {}

    public function form(): View
    {
        Gate::authorize('create', DeliverySchedule::class);

        return view('schedule::create', $this->formOptions());
    }

    public function store(StoreDeliveryScheduleRequest $request): RedirectResponse
    {
        $schedule = $this->schedules->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.schedule.show', $schedule)
            ->with('success', __('Standing order created. First delivery :date.', [
                'date' => $schedule->next_run_date?->format('D, d M Y') ?? 'not scheduled',
            ]));
    }
}
