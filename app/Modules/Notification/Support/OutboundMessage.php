<?php

declare(strict_types=1);

namespace App\Modules\Notification\Support;

/**
 * One WhatsApp message bound for one number. Immutable: once the dispatcher
 * has built it, nothing downstream can change who it goes to.
 */
final readonly class OutboundMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $to,
        public string $template,
        public string $body,
        public array $payload = [],
    ) {}
}
