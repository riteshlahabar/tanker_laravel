<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use App\Modules\Customer\Requests\UpdateCustomerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerUpdateController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function form(User $customer): View
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        return view('customer::edit', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return redirect()
            ->route('admin.customer.index')
            ->with('success', __('Customer updated successfully.'));
    }
}
