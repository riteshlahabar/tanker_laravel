<?php

declare(strict_types=1);

namespace App\Modules\Setting\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class SettingEditController
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function __invoke(): View
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        return view('setting::edit', ['settings' => $this->settings->all_()]);
    }
}
