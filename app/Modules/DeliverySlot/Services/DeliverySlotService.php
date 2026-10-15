<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Services;

use App\Modules\Core\Services\BaseService;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;

final class DeliverySlotService extends BaseService implements DeliverySlotServiceInterface
{
    public function __construct(private readonly DeliverySlotRepositoryInterface $slots) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliverySlot
    {
        return $this->transaction(fn (): DeliverySlot => $this->slots->store($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DeliverySlot $slot, array $data): DeliverySlot
    {
        return $this->transaction(fn (): DeliverySlot => $this->slots->modify($slot->id, $data));
    }

    /**
     * Soft delete: orders and schedules keep their snapshot label, and the
     * foreign keys null out rather than cascade.
     */
    public function delete(DeliverySlot $slot): void
    {
        $this->transaction(fn (): bool => $this->slots->remove($slot->id));
    }

    public function setStatus(DeliverySlot $slot, bool $status): DeliverySlot
    {
        return $this->slots->toggleStatus($slot->id, $status);
    }
}
