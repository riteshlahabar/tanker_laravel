<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * The scan screen: pair the owner's WhatsApp by showing the service's QR.
 *
 * The page renders whatever the service says right now and then lets a small
 * poller keep it current — see WhatsappScanStateController, which answers the
 * same two reads as JSON.
 */
final class WhatsappScanController
{
    public function __construct(private readonly WhatsAppScanClientInterface $scan) {}

    public function __invoke(): View
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        $status = $this->scan->status();

        return view('notification::scan', [
            'status' => $status,
            // Asking for a QR while connected would only be answered with
            // "not needed", so it is not asked.
            'qr' => $status->connected ? null : $this->scan->qr(),
            'driver' => config('services.whatsapp.driver'),
            'baseUrl' => config('services.whatsapp_scan.base_url'),
            'configured' => $this->scan->configured(),
        ]);
    }
}
