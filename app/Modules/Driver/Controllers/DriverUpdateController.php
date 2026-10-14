<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Requests\UpdateDriverRequest;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DriverUpdateController
{
    public function __construct(
        private readonly DriverServiceInterface $drivers,
        private readonly TankerRepositoryInterface $tankers,
    ) {}

    public function form(User $driver): View
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $currentTankerId = $driver->currentAssignment?->tanker_id;

        return view('driver::edit', [
            'driver' => $driver,
            'tankerOptions' => $this->tankers->assignableOptions($currentTankerId),
            'currentTankerId' => $currentTankerId,
        ]);
    }

    public function update(UpdateDriverRequest $request, User $driver): RedirectResponse
    {
        $this->drivers->update($driver, $request->validated());

        return redirect()
            ->route('admin.driver.index')
            ->with('success', __('Driver updated successfully.'));
    }
}
