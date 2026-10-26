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
        // Every column `SpotResource` serialises, or the ones left out come
        // back null — and `(bool) null` is false, which had the app drawing a
        // "blocked" marker against spots this very scope guarantees are
        // available.
        return $this->model->newQuery()
            ->usable()
            ->orderBy('name')
            ->get([
                'id', 'name', 'address', 'refill_charge',
                'water_type', 'contact_person', 'contact_mobile', 'is_available', 'status',
            ]);
    }
}
