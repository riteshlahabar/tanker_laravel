<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers;

use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SlotDeleteController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function __invoke(DeliverySlot $slot): RedirectResponse
    {
        Gate::authorize('delete', $slot);

        $this->slots->delete($slot);

        return back()->with('success', __('Delivery slot deleted.'));
    }
}
