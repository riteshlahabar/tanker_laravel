<?php

declare(strict_types=1);

namespace App\Modules\Driver\Repositories;

use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Core\Repositories\RoleScopedUserRepository;
use App\Modules\Driver\Contracts\DriverRepositoryInterface;

final class DriverRepository extends RoleScopedUserRepository implements DriverRepositoryInterface
{
    protected function role(): RoleEnum
    {
        return RoleEnum::Driver;
    }
}
