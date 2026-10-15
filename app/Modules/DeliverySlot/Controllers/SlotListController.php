<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Controllers;

use App\Modules\DeliverySlot\DataTables\DeliverySlotDataTable;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SlotListController
{
    public function __invoke(DeliverySlotDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', DeliverySlot::class);

        return $dataTable->render('slot::index');
    }
}
