<?php

declare(strict_types=1);

namespace App\Modules\Notification\Support;

/**
 * What the scan service says about its WhatsApp connection, as the screen
 * needs to read it.
 *
 * A value object rather than the raw array so the blade cannot go looking for
 * a key the service stopped sending — the deployed service is already a little
 * ahead of the copy in `whatsapp scan.zip`.
 */
final readonly class ScanStatus
{
    public function __construct(
        public bool $reachable,
        public bool $connected,
        public string $state,
        public ?string $number = null,
        public ?string $lastError = null,
        public ?string $disconnectReason = null,
        public ?int $disconnectCode = null,
        public int $reconnectAttempt = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  the /status body
     */
    public static function fromPayload(array $payload): self
    {
        $disconnect = is_array($payload['lastDisconnect'] ?? null) ? $payload['lastDisconnect'] : [];

        return new self(
            reachable: true,
            connected: (bool) ($payload['connected'] ?? false),
            state: (string) ($payload['state'] ?? 'unknown'),
            number: self::stringOrNull($payload['number'] ?? null),
            lastError: self::stringOrNull($payload['lastError'] ?? null),
            disconnectReason: self::stringOrNull($disconnect['reason'] ?? null),
            disconnectCode: isset($disconnect['code']) ? (int) $disconnect['code'] : null,
            reconnectAttempt: (int) ($payload['reconnectAttempt'] ?? 0),
        );
    }

    public static function unreachable(string $why): self
    {
        return new self(reachable: false, connected: false, state: 'unreachable', lastError: $why);
    }

    /**
     * A session the service cannot rescue on its own: WhatsApp rejected the
     * stored login, so it will keep retrying a pairing that will never work
     * and never offer a QR. Logging out is what clears it.
     */
    public function needsLogout(): bool
    {
        return $this->reachable
            && ! $this->connected
            && $this->disconnectCode === 401
            && $this->reconnectAttempt > 0;
    }

    public function badge(): string
    {
        return match (true) {
            ! $this->reachable => 'danger',
            $this->connected => 'success',
            default => 'warning',
        };
    }

    public function label(): string
    {
        return match (true) {
            ! $this->reachable => 'Service unreachable',
            $this->connected => 'Connected',
            default => ucfirst(str_replace('_', ' ', $this->state)),
        };
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
