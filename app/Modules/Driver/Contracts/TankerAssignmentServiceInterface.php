<?php

declare(strict_types=1);

namespace App\Modules\Driver\Contracts;

use App\Models\User;
use App\Modules\Tanker\Models\TankerAssignment;

interface TankerAssignmentServiceInterface
{
    /**
     * Give the driver this tanker, closing any assignment either side already
     * holds. Passing null just releases the driver's current tanker.
     */
    public function assign(User $driver, ?int $tankerId, ?string $reason = null): ?TankerAssignment;

    public function release(User $driver, ?string $reason = null): void;

    public function currentTankerId(User $driver): ?int;
}
