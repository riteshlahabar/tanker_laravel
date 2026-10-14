<?php

declare(strict_types=1);

namespace App\Modules\Notification\Support;

final readonly class GatewayResult
{
    private function __construct(
        public bool $delivered,
        public string $response,
    ) {}

    public static function sent(string $response = 'ok'): self
    {
        return new self(true, $response);
    }

    public static function failed(string $response): self
    {
        return new self(false, $response);
    }
}
