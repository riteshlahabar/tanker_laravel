<?php

declare(strict_types=1);

namespace App\Modules\Setting\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use Illuminate\Http\JsonResponse;

final class SettingShowController
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->settings->all_());
    }
}
