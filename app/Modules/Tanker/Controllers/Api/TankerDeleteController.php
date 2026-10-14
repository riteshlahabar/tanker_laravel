<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Tanker\Contracts\TankerServiceInterface;
use App\Modules\Tanker\Exceptions\TankerInUseException;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TankerDeleteController
{
    public function __construct(private readonly TankerServiceInterface $tankers) {}

    public function __invoke(Tanker $tanker): JsonResponse
    {
        Gate::authorize('delete', $tanker);

        try {
            $this->tankers->delete($tanker);
        } catch (TankerInUseException $e) {
            return ApiResponse::error($e->getMessage());
        }

        return ApiResponse::success(null, __('Tanker deleted successfully.'));
    }
}
