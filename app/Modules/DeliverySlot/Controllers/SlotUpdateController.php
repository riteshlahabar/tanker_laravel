<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers;

use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\DeliverySlot\Requests\UpdateDeliverySlotRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SlotUpdateController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function form(DeliverySlot $slot): View
    {
        Gate::authorize('update', $slot);

        return view('slot::edit', ['slot' => $slot]);
    }

    public function update(UpdateDeliverySlotRequest $request, DeliverySlot $slot): RedirectResponse
    {
        $this->slots->update($slot, $request->validated());

        return redirect()
            ->route('admin.slot.index')
            ->with('success', __('Delivery slot updated.'));
    }
}
