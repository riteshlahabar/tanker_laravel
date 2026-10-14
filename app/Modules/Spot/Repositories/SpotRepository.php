<?php

declare(strict_types=1);

namespace App\Modules\Spot\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use Illuminate\Support\Collection;

final class SpotRepository extends BaseRepository implements SpotRepositoryInterface
{
    public function model(): string
    {
        return Spot::class;
    }

    /**
     * @return Collection<int, Spot>
     */
    public function usable(): Collection
    {
        return $this->model->newQuery()
            ->usable()
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'refill_charge']);
    }

    /**
     * @return Collection<int, Spot>
     */
    public function nearest(float $latitude, float $longitude, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->usable()
            ->nearest($latitude, $longitude)
            ->limit($limit)
            ->get();
    }
}
