<?php

declare(strict_types=1);

namespace App\Modules\Core\Controllers\Api\Concerns;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Core\Support\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shared by the admin master-data list endpoints: same paging, same search
 * handling, same response shape.
 */
trait RespondsWithList
{
    /**
     * @param  class-string<JsonResource>  $resource
     * @param  array<int, string>  $searchable
     */
    private function listResponse(
        Builder $query,
        Request $request,
        string $resource,
        array $searchable = [],
    ): JsonResponse {
        $search = $request->string('search')->value();

        $page = $query
            ->when($search !== '' && $searchable !== [], fn (Builder $q) => $q
                ->whereAny($searchable, 'like', "%{$search}%"))
            ->when($request->has('status') && $request->input('status') !== '', fn (Builder $q) => $q
                ->where('status', $request->boolean('status')))
            ->paginate(Paginated::perPage($request->input('per_page')));

        return ApiResponse::success(Paginated::of($page, $resource));
    }
}
