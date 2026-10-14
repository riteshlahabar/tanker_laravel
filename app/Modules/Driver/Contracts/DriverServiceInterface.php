<?php

declare(strict_types=1);

namespace App\Modules\Driver\Contracts;

use App\Models\User;

interface DriverServiceInterface
{
    /**
     * @param  array<string, mixed>  $data  may carry tanker_id
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data  may carry tanker_id; blank password keeps the old one
     */
    public function update(User $driver, array $data): User;

    public function delete(User $driver): void;

    public function setStatus(User $driver, bool $status): User;

    public function changeTanker(User $driver, ?int $tankerId, ?string $reason): void;
}
