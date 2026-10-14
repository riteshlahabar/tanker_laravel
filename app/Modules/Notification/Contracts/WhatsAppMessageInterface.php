<?php

declare(strict_types=1);

namespace App\Modules\Notification\Contracts;

/**
 * A message the system wants to send: who it goes to, and what it says. One
 * class per notification, so the wording of each lives in exactly one place.
 */
interface WhatsAppMessageInterface
{
    /**
     * Mobile numbers this message goes to. Empty means nobody is reachable and
     * the message is skipped rather than failing.
     *
     * @return array<int, string>
     */
    public function recipients(): array;

    /**
     * Stable template name, recorded against every sent message.
     */
    public function template(): string;

    public function body(): string;

    /**
     * Structured data sent alongside the text, so the Node service can use a
     * real WhatsApp template later instead of plain text.
     *
     * @return array<string, mixed>
     */
    public function payload(): array;
}
