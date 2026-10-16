<?php

declare(strict_types=1);

namespace App\Modules\Notification\Support;

/**
 * The QR the scan service is currently offering, if any.
 *
 * Three outcomes the screen has to tell apart, which is why this is not just
 * a nullable string: a code to show, nothing to show *because the phone is
 * already paired*, and nothing to show *yet*.
 */
final readonly class ScanQr
{
    private function __construct(
        public ?string $imageDataUrl,
        public bool $connected,
        public ?string $message = null,
    ) {}

    public static function image(string $dataUrl): self
    {
        return new self($dataUrl, false);
    }

    public static function alreadyConnected(): self
    {
        return new self(null, true, 'Already connected — no QR needed.');
    }

    /**
     * The service answers 404 while it is starting up or between codes.
     */
    public static function notReady(?string $message = null): self
    {
        return new self(null, false, $message ?: 'QR not available yet. Please retry.');
    }

    public function hasImage(): bool
    {
        return $this->imageDataUrl !== null;
    }
}
