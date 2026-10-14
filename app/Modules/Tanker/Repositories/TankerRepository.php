<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TankerRepository extends BaseRepository implements TankerRepositoryInterface
{
    public function model(): string
    {
        return Tanker::class;
    }

    /**
     * @return Collection<int, Tanker>
     */
    public function assignable(?int $keepTankerId = null): Collection
    {
        return $this->model->newQuery()
            ->active()
            ->where(fn (Builder $query) => $query
                ->whereDoesntHave('currentAssignment')
                ->when($keepTankerId !== null, fn (Builder $q) => $q->orWhere('id', $keepTankerId)))
            ->orderBy('vehicle_name')
            ->get(['id', 'vehicle_name', 'vehicle_number']);
    }

    /**
     * @return array<int, string>
     */
    public function assignableOptions(?int $keepTankerId = null): array
    {
        return $this->assignable($keepTankerId)
            ->mapWithKeys(fn (Tanker $tanker): array => [$tanker->id => $tanker->label()])
            ->all();
    }
}
