<?php

declare(strict_types=1);

namespace App\Modules\Setting\Controllers;

use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Setting\Requests\UpdateSettingRequest;
use Illuminate\Http\RedirectResponse;

final class SettingUpdateController
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function __invoke(UpdateSettingRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            $this->settings->put($key, $value === null ? null : (string) $value);
        }

        return back()->with('success', __('Settings saved.'));
    }
}
