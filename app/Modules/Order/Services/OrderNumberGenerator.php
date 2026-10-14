<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Contracts\OrderNumberGeneratorInterface;

/**
 * Derived from the row id, so two orders created at the same instant can never
 * collide on the reference.
 */
final class OrderNumberGenerator implements OrderNumberGeneratorInterface
{
    private const PREFIX = 'TNK';

    public function forId(int $id): string
    {
        return sprintf('%s-%s-%05d', self::PREFIX, now()->format('Y'), $id);
    }
}
