<?php

declare(strict_types=1);

namespace App\Modules\Notification\Jobs;

use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Models\WhatsappMessage;
use App\Modules\Notification\Support\OutboundMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/**
 * Sending happens on the queue, so a slow or unreachable WhatsApp service can
 * never hold up an order being saved.
 */
final class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Spread the retries: a gateway that is down briefly recovers on its own.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    public function __construct(private readonly int $messageId) {}

    public function handle(WhatsAppGatewayInterface $gateway): void
    {
        $record = WhatsappMessage::query()->find($this->messageId);

        if ($record === null || $record->status === WhatsappMessage::STATUS_SENT) {
            return;
        }

        $result = $gateway->send(new OutboundMessage(
            to: $record->to,
            template: (string) $record->template,
            body: (string) $record->body,
            payload: $record->payload ?? [],
        ));

        if ($result->delivered) {
            $record->markSent($result->response);

            return;
        }

        $record->markFailed($result->response);

        // Fail the job so the queue retries with backoff; the final attempt
        // leaves the row marked failed for the admin log.
        throw new RuntimeException('WhatsApp send failed: '.$result->response);
    }
}
