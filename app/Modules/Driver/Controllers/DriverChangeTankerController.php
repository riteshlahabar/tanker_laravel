<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Requests\ChangeTankerRequest;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Swapping a driver's tanker — the case the owner described as "sometimes the
 * tanker may change if a driver is absent".
 */
final class DriverChangeTankerController
{
    public function __construct(
        private readonly DriverServiceInterface $drivers,
        private readonly TankerRepositoryInterface $tankers,
    ) {}

    public function form(User $driver): View
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        $currentTankerId = $driver->currentAssignment?->tanker_id;

        return view('driver::change-tanker', [
            'driver' => $driver,
            'tankerOptions' => $this->tankers->assignableOptions($currentTankerId),
            'currentTankerId' => $currentTankerId,
            'history' => $driver->assignments()->with('tanker:id,vehicle_name,vehicle_number')
                ->latest('assigned_from')->limit(10)->get(),
        ]);
    }

    public function update(ChangeTankerRequest $request, User $driver): RedirectResponse
    {
        $this->drivers->changeTanker(
            $driver,
            $request->input('tanker_id') === null ? null : (int) $request->input('tanker_id'),
            $request->string('reason')->value() ?: null,
        );

        return redirect()
            ->route('admin.driver.index')
            ->with('success', __('Tanker assignment updated.'));
    }
}
