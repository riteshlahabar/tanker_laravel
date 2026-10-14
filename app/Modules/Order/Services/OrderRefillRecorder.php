<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;

/**
 * The driver may fill at a different spot than the admin picked. Changing it
 * re-snapshots the refill charge, so the spot report and the order agree.
 */
final class OrderRefillRecorder
{
    public function __construct(private readonly SpotRepositoryInterface $spots) {}

    public function recordSpot(Order $order, mixed $spotId): void
    {
        if ($spotId === null || $spotId === '') {
            return;
        }

        $spot = $this->spots->findOrFail((int) $spotId);

        if (! $spot->is_available || ! $spot->status) {
            throw OrderAssignmentException::spotUnusable();
        }

        $order->update([
            'spot_id' => $spot->id,
            'refill_charge' => (float) $spot->refill_charge,
        ]);
    }
}
