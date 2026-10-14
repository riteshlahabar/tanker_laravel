<?php

declare(strict_types=1);

namespace App\Modules\Notification\Contracts;

use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\OutboundMessage;

/**
 * The seam between this application and however WhatsApp is actually sent.
 * Swapping LogGateway for NodeApiGateway is a WHATSAPP_DRIVER change in .env —
 * no application code moves.
 */
interface WhatsAppGatewayInterface
{
    public function send(OutboundMessage $message): GatewayResult;
}
