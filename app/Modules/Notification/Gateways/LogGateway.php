<?php

declare(strict_types=1);

namespace App\Modules\Notification\Gateways;

use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\OutboundMessage;
use Illuminate\Support\Facades\Log;

/**
 * The default until the owner's Node.js WhatsApp service exists. The message
 * is already recorded in `whatsapp_messages` by the dispatcher; this writes it
 * to the application log too so it is visible during development.
 */
final class LogGateway implements WhatsAppGatewayInterface
{
    public function send(OutboundMessage $message): GatewayResult
    {
        Log::channel(config('logging.default'))->info('WhatsApp (not sent — log driver)', [
            'to' => $message->to,
            'template' => $message->template,
            'body' => $message->body,
        ]);

        return GatewayResult::sent('logged');
    }
}
