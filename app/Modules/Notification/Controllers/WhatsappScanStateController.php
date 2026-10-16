<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Status and QR as JSON, for the scan screen's poller.
 *
 * A QR lasts about twenty seconds before the service replaces it, so the page
 * cannot be a static render — it asks again every few seconds until the phone
 * is paired, then stops.
 */
final class WhatsappScanStateController
{
    public function __construct(private readonly WhatsAppScanClientInterface $scan) {}

    public function __invoke(): JsonResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        $status = $this->scan->status();
        $qr = $status->connected ? null : $this->scan->qr();

        return response()->json([
            'connected' => $status->connected,
            'reachable' => $status->reachable,
            'state' => $status->state,
            'label' => $status->label(),
            'badge' => $status->badge(),
            'number' => $status->number,
            'last_error' => $status->lastError,
            'needs_logout' => $status->needsLogout(),
            'qr_image' => $qr?->imageDataUrl,
            'qr_message' => $qr?->message,
        ]);
    }
}
