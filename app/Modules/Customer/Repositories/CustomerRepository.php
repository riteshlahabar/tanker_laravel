<?php

declare(strict_types=1);

namespace App\Modules\Customer\Repositories;

use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Core\Repositories\RoleScopedUserRepository;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;

final class CustomerRepository extends RoleScopedUserRepository implements CustomerRepositoryInterface
{
    protected function role(): RoleEnum
    {
        return RoleEnum::Customer;
    }

    /**
     * `model_has_roles.model_id` carries no foreign key to `users`, so the
     * pivot has to be detached by hand — a force delete alone would leave a row
     * pointing at an id that no longer exists, and the next customer to be
     * given that id would inherit the role.
     */
    public function purge(int $id): bool
    {
        $customer = $this->findOrFail($id);
        $customer->roles()->detach();

        return (bool) $customer->forceDelete();
    }
}
