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
}
