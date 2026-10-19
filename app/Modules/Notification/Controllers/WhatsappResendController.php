<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Jobs\SendWhatsAppMessage;
use App\Modules\Notification\Models\WhatsappMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Put one logged message back on the queue.
 *
 * Needed because the retry budget is finite by design: once six tries over
 * fifty minutes are spent the row is marked failed and nothing will touch it
 * again. Before this, the only way to recover a message the gateway had
 * refused was to repeat whatever business action produced it — re-place an
 * order, re-assign a driver — which is not something an owner should have to
 * do to send a notification.
 *
 * It re-queues the *recorded* row rather than rebuilding the message, so the
 * wording, the recipient and the template are exactly what was composed at the
 * time, and the attempt history is kept.
 */
final class WhatsappResendController
{
    public function __invoke(WhatsappMessage $message): RedirectResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        // A delivered message must never be sendable twice: the customer would
        // get the same notice again, and the log would lose the record of when
        // it actually went.
        if (! $message->isResendable()) {
            return back()->with('error', __('That message has already been delivered.'));
        }

        $message->queueForResend();

        SendWhatsAppMessage::dispatch($message->id);

        return back()->with('success', __('Message to :number queued again.', [
            'number' => $message->to,
        ]));
    }
}
