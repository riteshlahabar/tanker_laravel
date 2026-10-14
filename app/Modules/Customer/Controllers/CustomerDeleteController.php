<?php

declare(strict_types=1);

namespace App\Modules\Customer\Controllers;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Customer\Contracts\CustomerServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CustomerDeleteController
{
    public function __construct(private readonly CustomerServiceInterface $customers) {}

    public function __invoke(User $customer): RedirectResponse
    {
        Gate::authorize(PermissionEnum::CustomerManage->value);

        $this->customers->delete($customer);

        return back()->with('success', __('Customer deleted successfully.'));
    }
}
