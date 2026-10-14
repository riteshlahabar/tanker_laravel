<?php

declare(strict_types=1);

namespace App\Modules\Spot\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use Illuminate\Support\Collection;

interface SpotRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Spots that can be used for a refill right now.
     *
     * @return Collection<int, Spot>
     */
    public function usable(): Collection;

    /**
     * Usable spots ordered by distance from a point.
     *
     * @return Collection<int, Spot>
     */
    public function nearest(float $latitude, float $longitude, int $limit = 10): Collection;
}
