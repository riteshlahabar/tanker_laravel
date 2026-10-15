<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Support\Collection;

final class DeliverySlotRepository extends BaseRepository implements DeliverySlotRepositoryInterface
{
    public function model(): string
    {
        return DeliverySlot::class;
    }

    /**
     * @return Collection<int, DeliverySlot>
     */
    public function active(): Collection
    {
        return $this->model->newQuery()->active()->ordered()->get();
    }

    /**
     * @return array<int, string>
     */
    public function options(): array
    {
        return $this->active()
            ->mapWithKeys(fn (DeliverySlot $slot): array => [$slot->id => $slot->label()])
            ->all();
    }

    public function findOrNull(int $id): ?DeliverySlot
    {
        return $this->model->newQuery()->find($id);
    }
}
