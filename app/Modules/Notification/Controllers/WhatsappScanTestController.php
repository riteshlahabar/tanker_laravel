<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use App\Modules\Notification\Requests\WhatsappScanTestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Sends one message straight through the scan service.
 *
 * Deliberately **not** queued and not written to `whatsapp_messages`: this is
 * the owner proving the pairing works, so the answer has to come back on the
 * same request. Every real notification still goes through the dispatcher and
 * the queue.
 */
final class WhatsappScanTestController
{
    public function __construct(private readonly WhatsAppScanClientInterface $scan) {}

    public function __invoke(WhatsappScanTestRequest $request): RedirectResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        $result = $this->scan->send($request->phone(), $request->body());

        return redirect()
            ->route('admin.whatsapp.scan')
            ->with(
                $result->delivered ? 'success' : 'error',
                $result->delivered
                    ? 'Test message sent to '.$request->phone().'.'
                    : 'Could not send: '.$result->response,
            );
    }
}
