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

    public int $tries = 6;

    /**
     * Spread the retries: a gateway that is down briefly recovers on its own.
     *
     * The first two delays are unchanged, so a momentary blip still clears in
     * seconds. What is new is the tail — 30s + 2m + 5m + 15m + 30m is roughly
     * **fifty minutes** of budget against the old three tries' two and a half.
     *
     * That window is the whole point. An outage longer than the budget does not
     * delay a message, it **destroys** it, and that is precisely how the
     * `order.assigned` pair was lost on 2026-10-17 while a duplicate process
     * was evicting the WhatsApp session every ninety seconds. Fifty minutes
     * would have carried them through it.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300, 900, 1800];

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

        // Failed *for good* only once the budget is spent. Until then the row
        // stays pending with the reason recorded, so the log does not call a
        // message dead while the queue is still going to retry it.
        $this->attempts() >= $this->tries
            ? $record->markFailed($result->response)
            : $record->markRetrying($result->response);

        // Fail the job so the queue retries with backoff; the final attempt
        // leaves the row marked failed for the admin log.
        throw new RuntimeException('WhatsApp send failed: '.$result->response);
    }
}
