<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Target of the status switch rendered in every DataTable row.
 */
final class TankerStatusController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function __invoke(Request $request, Tanker $tanker): JsonResponse
    {
        Gate::authorize('update', $tanker);

        $request->validate(['status' => ['required', 'boolean']]);

        return ApiResponse::success(
            ['status' => $this->tankers->setStatus($tanker, $request->boolean('status'))->status],
            __('Status updated successfully.'),
        );
    }
}
