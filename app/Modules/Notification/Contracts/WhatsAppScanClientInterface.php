<?php

declare(strict_types=1);

namespace App\Modules\Notification\Contracts;

use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\ScanQr;
use App\Modules\Notification\Support\ScanStatus;

/**
 * The owner's Node.js scan service, as the panel talks to it. Controllers and
 * the gateway depend on this, so a test drives them with a fake rather than
 * an HTTP fake.
 */
interface WhatsAppScanClientInterface
{
    public function configured(): bool;

    public function status(): ScanStatus;

    public function qr(): ScanQr;

    /**
     * @param  string  $phone  any stored form; it is qualified before sending
     */
    public function send(string $phone, string $message): GatewayResult;

    /**
     * Drops the stored session so the next QR is a fresh pairing. The only
     * way out of a login WhatsApp has rejected.
     */
    public function logout(): GatewayResult;

    /**
     * Restarts the socket but keeps the session — for a service that is merely
     * stuck rather than logged out.
     */
    public function restart(): GatewayResult;
}
