<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use App\Modules\Notification\Requests\WhatsappScanActionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Restart or log the scan service out.
 *
 * The two live together because they are one decision with two strengths:
 * restart keeps the stored session and is for a service that is merely stuck,
 * logout throws the session away and is the only way out of a login WhatsApp
 * has rejected (a 401 the service will otherwise retry for ever).
 */
final class WhatsappScanActionController
{
    public function __construct(private readonly WhatsAppScanClientInterface $scan) {}

    public function __invoke(WhatsappScanActionRequest $request): RedirectResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        $logout = $request->isLogout();

        $result = $logout ? $this->scan->logout() : $this->scan->restart();

        $notice = $logout
            ? 'Logged out. Scan the new QR to pair WhatsApp again.'
            : 'Restart requested. Give it a few seconds.';

        return redirect()
            ->route('admin.whatsapp.scan')
            ->with($result->delivered ? 'success' : 'error', $result->delivered ? $notice : $result->response);
    }
}
