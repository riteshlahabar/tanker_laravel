<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Contracts;

use App\Modules\DeliverySlot\Models\DeliverySlot;

interface DeliverySlotServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliverySlot;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DeliverySlot $slot, array $data): DeliverySlot;

    public function delete(DeliverySlot $slot): void;

    public function setStatus(DeliverySlot $slot, bool $status): DeliverySlot;
}
