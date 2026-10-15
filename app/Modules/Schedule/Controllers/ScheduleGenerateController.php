<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Schedule\Contracts\ScheduledOrderGeneratorInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * "Generate now" — the same run the scheduler does at 00:05, for an owner who
 * does not want to wait for tonight.
 */
final class ScheduleGenerateController
{
    public function __construct(private readonly ScheduledOrderGeneratorInterface $generator) {}

    public function __invoke(Request $request): RedirectResponse
    {
        Gate::authorize(PermissionEnum::ScheduleManage->value);

        $request->validate(['date' => ['nullable', 'date']]);

        $date = $request->input('date');
        $target = is_string($date) && $date !== ''
            ? Carbon::parse($date)->startOfDay()
            : Carbon::tomorrow();

        $report = $this->generator->run($target);

        return back()->with(
            $report->failedCount() > 0 ? 'error' : 'success',
            __(':summary (up to :date)', [
                'summary' => $report->summary(),
                'date' => $target->format('D, d M Y'),
            ]),
        );
    }
}
