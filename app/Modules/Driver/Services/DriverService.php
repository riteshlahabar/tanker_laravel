<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Models\User;
use App\Modules\Core\Services\BaseService;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Contracts\TankerAssignmentServiceInterface;

final class DriverService extends BaseService implements DriverServiceInterface
{
    public function __construct(
        private readonly DriverRepositoryInterface $drivers,
        private readonly TankerAssignmentServiceInterface $assignments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $tankerId = $this->pullTankerId($data);

        return $this->transaction(function () use ($data, $tankerId): User {
            $driver = $this->drivers->store($data);
            $this->assignments->assign($driver, $tankerId);

            return $driver;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $driver, array $data): User
    {
        $tankerId = $this->pullTankerId($data);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $this->transaction(function () use ($driver, $data, $tankerId): User {
            $updated = $this->drivers->modify($driver->id, $data);
            $this->assignments->assign($updated, $tankerId);

            return $updated;
        });
    }

    /**
     * Soft delete, with the tanker released and app tokens revoked — past
     * deliveries stay in the reports.
     */
    public function delete(User $driver): void
    {
        $this->transaction(function () use ($driver): void {
            $this->assignments->release($driver, 'Driver removed');
            $driver->tokens()->delete();
            $this->drivers->remove($driver->id);
        });
    }

    public function setStatus(User $driver, bool $status): User
    {
        if (! $status) {
            $driver->tokens()->delete();
        }

        return $this->drivers->toggleStatus($driver->id, $status);
    }

    public function changeTanker(User $driver, ?int $tankerId, ?string $reason): void
    {
        $this->assignments->assign($driver, $tankerId, $reason);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pullTankerId(array &$data): ?int
    {
        $tankerId = $data['tanker_id'] ?? null;
        unset($data['tanker_id']);

        return $tankerId === null || $tankerId === '' ? null : (int) $tankerId;
    }
}
