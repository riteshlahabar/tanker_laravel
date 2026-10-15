<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers;

use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\DeliverySlot\Requests\StoreDeliverySlotRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SlotCreateController
{
    public function __construct(private readonly DeliverySlotServiceInterface $slots) {}

    public function form(): View
    {
        Gate::authorize('create', DeliverySlot::class);

        return view('slot::create');
    }

    public function store(StoreDeliverySlotRequest $request): RedirectResponse
    {
        $this->slots->create($request->validated());

        return redirect()
            ->route('admin.slot.index')
            ->with('success', __('Delivery slot added.'));
    }
}
