<?php

declare(strict_types=1);

namespace App\Modules\Setting\Controllers\Api;

use App\Modules\Core\Support\ApiResponse;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Setting\Requests\UpdateSettingRequest;
use Illuminate\Http\JsonResponse;

final class SettingUpdateController
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function __invoke(UpdateSettingRequest $request): JsonResponse
    {
        foreach ($request->validated() as $key => $value) {
            $this->settings->put($key, $value === null ? null : (string) $value);
        }

        return ApiResponse::success($this->settings->all_(), __('Settings saved.'));
    }
}
