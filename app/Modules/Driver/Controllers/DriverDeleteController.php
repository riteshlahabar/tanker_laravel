<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DriverDeleteController
{
    public function __construct(private readonly DriverServiceInterface $drivers) {}

    public function __invoke(User $driver): RedirectResponse
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $this->drivers->delete($driver);

        return back()->with('success', __('Driver deleted successfully.'));
    }
}
