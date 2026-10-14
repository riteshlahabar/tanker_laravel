<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use App\Modules\Customer\Requests\StoreCustomerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerCreateController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function form(): View
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        return view('customer::create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->customers->create($request->validated());

        return redirect()
            ->route('admin.customer.index')
            ->with('success', __('Customer added successfully.'));
    }
}
