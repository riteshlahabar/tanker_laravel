<?php

declare(strict_types=1);

namespace App\Modules\Spot\Services;

use App\Modules\Core\Services\BaseService;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Contracts\SpotServiceInterface;
use App\Modules\Spot\Models\Spot;

final class SpotService extends BaseService implements SpotServiceInterface
{
    public function __construct(private readonly SpotRepositoryInterface $spots) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Spot
    {
        return $this->transaction(fn (): Spot => $this->spots->store($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Spot $spot, array $data): Spot
    {
        return $this->transaction(fn (): Spot => $this->spots->modify($spot->id, $data));
    }

    public function delete(Spot $spot): void
    {
        $this->transaction(fn (): bool => $this->spots->remove($spot->id));
    }

    public function setStatus(Spot $spot, bool $status): Spot
    {
        return $this->spots->toggleStatus($spot->id, $status);
    }
}
