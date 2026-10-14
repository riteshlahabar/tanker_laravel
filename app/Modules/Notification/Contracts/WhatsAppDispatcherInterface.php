<?php

declare(strict_types=1);

namespace App\Modules\Notification\Contracts;

interface WhatsAppDispatcherInterface
{
    /**
     * Record and queue a message for each of its recipients.
     *
     * @return int how many were queued
     */
    public function dispatch(WhatsAppMessageInterface $message): int;
}
