<?php

declare(strict_types=1);

namespace App\Modules\Notification\Gateways;

use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\OutboundMessage;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Posts to the owner's Node.js WhatsApp service. Switched on by setting
 * WHATSAPP_DRIVER=node and WHATSAPP_URL in .env; no other change is needed.
 */
final class NodeApiGateway implements WhatsAppGatewayInterface
{
    public function __construct(
        private readonly string $url,
        private readonly ?string $token = null,
        private readonly int $timeout = 15,
    ) {}

    public function send(OutboundMessage $message): GatewayResult
    {
        if ($this->url === '') {
            return GatewayResult::failed('WHATSAPP_URL is not configured.');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->when($this->token !== null && $this->token !== '',
                    fn ($client) => $client->withToken($this->token))
                ->asJson()
                ->post($this->url, [
                    'to' => $message->to,
                    'template' => $message->template,
                    'message' => $message->body,
                    'data' => $message->payload,
                ]);
        } catch (Throwable $e) {
            // Thrown back to the job so its retry/backoff can take over.
            return GatewayResult::failed($e->getMessage());
        }

        return $response->successful()
            ? GatewayResult::sent($response->body())
            : GatewayResult::failed('HTTP '.$response->status().': '.$response->body());
    }
}
