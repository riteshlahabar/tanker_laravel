<?php

declare(strict_types=1);

namespace App\Modules\Notification\Gateways;

use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\OutboundMessage;

/**
 * Sends through the owner's QR-scan service. Switched on with
 * `WHATSAPP_DRIVER=scan`; no other change is needed.
 *
 * The service takes `{phone, message}` and nothing else, so the template name
 * and payload an [OutboundMessage] carries stay on this side — they are
 * already recorded against the `whatsapp_messages` row, which is where the
 * owner looks to see what was sent and why.
 */
final class ScanApiGateway implements WhatsAppGatewayInterface
{
    public function __construct(private readonly WhatsAppScanClientInterface $scan) {}

    public function send(OutboundMessage $message): GatewayResult
    {
        return $this->scan->send($message->to, $message->body);
    }
}
