<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Contracts\WhatsAppMessageInterface;
use App\Modules\Notification\Jobs\SendWhatsAppMessage;
use App\Modules\Notification\Models\WhatsappMessage;

/**
 * Records every message before queueing it, so nothing is lost if the queue or
 * the gateway is down.
 */
final class WhatsAppDispatcher implements WhatsAppDispatcherInterface
{
    public function dispatch(WhatsAppMessageInterface $message): int
    {
        $queued = 0;

        foreach ($this->uniqueRecipients($message) as $recipient) {
            $record = WhatsappMessage::query()->create([
                'to' => $recipient,
                'template' => $message->template(),
                'body' => $message->body(),
                'payload' => $message->payload(),
                'status' => WhatsappMessage::STATUS_PENDING,
            ]);

            SendWhatsAppMessage::dispatch($record->id);
            $queued++;
        }

        return $queued;
    }

    /**
     * Blank numbers are dropped, and the same number is not messaged twice for
     * one event (the owner may also be the customer on a test order).
     *
     * @return array<int, string>
     */
    private function uniqueRecipients(WhatsAppMessageInterface $message): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (string $to): string => trim($to), $message->recipients()),
            static fn (string $to): bool => $to !== '',
        )));
    }
}
