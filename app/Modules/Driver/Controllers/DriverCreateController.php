<?php

declare(strict_types=1);

namespace App\Modules\Driver\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Driver\Contracts\DriverServiceInterface;
use App\Modules\Driver\Requests\StoreDriverRequest;
use App\Modules\Tanker\Contracts\TankerRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DriverCreateController
{
    public function __construct(
        private readonly DriverServiceInterface $drivers,
        private readonly TankerRepositoryInterface $tankers,
    ) {}

    public function form(): View
    {
        Gate::authorize(PermissionEnum::DriverManage->value);

        return view('driver::create', [
            'tankerOptions' => $this->tankers->assignableOptions(),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $this->drivers->create($request->validated());

        return redirect()
            ->route('admin.driver.index')
            ->with('success', __('Driver added successfully.'));
    }
}
