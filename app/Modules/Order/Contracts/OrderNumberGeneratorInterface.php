<?php

declare(strict_types=1);

namespace App\Modules\Order\Contracts;

interface OrderNumberGeneratorInterface
{
    /**
     * Human-readable order reference, e.g. TNK-2026-00041.
     */
    public function forId(int $id): string;
}
