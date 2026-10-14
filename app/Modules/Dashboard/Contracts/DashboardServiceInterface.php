<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Contracts;

interface DashboardServiceInterface
{
    /**
     * Counters shown on the admin dashboard tiles.
     *
     * @return array<string, int|float>
     */
    public function summary(): array;
}
