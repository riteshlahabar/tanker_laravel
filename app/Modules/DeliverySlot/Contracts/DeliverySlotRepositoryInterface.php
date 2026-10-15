<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Support\Collection;

interface DeliverySlotRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, DeliverySlot>
     */
    public function active(): Collection;

    /**
     * id => "Morning (8:00 AM - 12:00 PM)" for a select element.
     *
     * @return array<int, string>
     */
    public function options(): array;

    /**
     * The slot, or null when the id does not exist. Unlike the inherited
     * find(), this does not throw — resolving an order's delivery_at treats a
     * missing slot as "no slot named" rather than an error.
     */
    public function findOrNull(int $id): ?DeliverySlot;
}
