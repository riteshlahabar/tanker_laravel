<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Services\DailyReportNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The "Send today's report now" button — same notifier the 23:59 schedule
 * uses, so testing it proves the scheduled run too.
 */
final class SendDailyReportController
{
    public function __construct(private readonly DailyReportNotifier $notifier) {}

    public function __invoke(Request $request, DailyReportNotifier $notifier): RedirectResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        $date = $request->date('date')?->toDateString() ?? now()->toDateString();
        $result = $this->notifier->sendFor($date);

        if ($result['recipient'] === null) {
            return back()->with('error', __('Set the owner WhatsApp number first.'));
        }

        return back()->with('success', __('Daily report queued to :number.', [
            'number' => $result['recipient'],
        ]));
    }
}
