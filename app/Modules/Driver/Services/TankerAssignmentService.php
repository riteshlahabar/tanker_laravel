<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Driver\Contracts\TankerAssignmentServiceInterface;
use App\Modules\Tanker\Models\TankerAssignment;

/**
 * Owns the one-tanker-per-driver rule. Reassignment closes the open row and
 * opens a new one, so the history in `tanker_assignments` stays intact.
 */
final class TankerAssignmentService extends BaseService implements TankerAssignmentServiceInterface
{
    public function assign(User $driver, ?int $tankerId, ?string $reason = null): ?TankerAssignment
    {
        if ($tankerId === null) {
            $this->release($driver, $reason);

            return null;
        }

        if ($this->currentTankerId($driver) === $tankerId) {
            return $this->openAssignmentFor($driver);
        }

        return $this->transaction(function () use ($driver, $tankerId, $reason): TankerAssignment {
            $this->closeOpenRows($driver, $tankerId, $reason);

            return TankerAssignment::query()->create([
                'driver_id' => $driver->id,
                'tanker_id' => $tankerId,
                'assigned_from' => now()->toDateString(),
                'reason' => $reason,
            ]);
        });
    }

    public function release(User $driver, ?string $reason = null): void
    {
        $this->transaction(fn () => TankerAssignment::query()
            ->where('driver_id', $driver->id)
            ->whereNull('assigned_to')
            ->update(['assigned_to' => now()->toDateString(), 'reason' => $reason]));
    }

    public function currentTankerId(User $driver): ?int
    {
        return $this->openAssignmentFor($driver)?->tanker_id;
    }

    private function openAssignmentFor(User $driver): ?TankerAssignment
    {
        return TankerAssignment::query()
            ->where('driver_id', $driver->id)
            ->whereNull('assigned_to')
            ->first();
    }

    /**
     * Closes the driver's open row and any open row on the target tanker — a
     * tanker cannot be run by two drivers at once.
     */
    private function closeOpenRows(User $driver, int $tankerId, ?string $reason): void
    {
        TankerAssignment::query()
            ->whereNull('assigned_to')
            ->where(fn ($query) => $query->where('driver_id', $driver->id)->orWhere('tanker_id', $tankerId))
            ->update(['assigned_to' => now()->toDateString(), 'reason' => $reason]);
    }
}
