<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Api\Admin;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use App\Modules\Schedule\Contracts\DeliveryScheduleRepositoryInterface;
use App\Modules\Schedule\Resources\DeliveryScheduleResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScheduleApiListController
{
    public function __construct(private readonly DeliveryScheduleRepositoryInterface $schedules) {}

    public function __invoke(Request $request): JsonResponse
    {
        $customerId = $request->input('customer_id');
        $search = $request->string('search')->value();

        $page = $this->schedules->visibleTo($request->user())
            ->with(['customer:id,first_name,last_name,mobile,address', 'slot', 'preferredDriver:id,first_name,last_name'])
            ->when($customerId !== null && $customerId !== '', fn (Builder $q) => $q
                ->where('customer_id', (int) $customerId))
            ->when($request->has('status') && $request->input('status') !== '', fn (Builder $q) => $q
                ->where('status', $request->boolean('status')))
            ->when($request->has('paused') && $request->input('paused') !== '', fn (Builder $q) => $q
                ->where('is_paused', $request->boolean('paused')))
            ->when($search !== '', fn (Builder $q) => $q
                ->whereHas('customer', fn (Builder $c) => $c
                    ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$search}%")))
            ->orderBy('next_run_date')
            ->orderByDesc('id')
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, DeliveryScheduleResource::class));
    }
}
